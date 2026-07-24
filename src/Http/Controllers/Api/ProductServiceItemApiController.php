<?php

namespace Zerp\ProductService\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Zerp\ProductService\Http\Requests\Api\StoreProductServiceItemApiRequest;
use Zerp\ProductService\Http\Requests\Api\UpdateProductServiceItemApiRequest;
use Zerp\ProductService\Models\ProductServiceItem;
use Zerp\ProductService\Models\ProductServiceTax;
use Zerp\ProductService\Models\WarehouseStock;

/**
 * REST API for the Product & Service catalog items. Mirrors the web
 * ProductServiceItemController: the manage-any/manage-own permission split
 * decides whether the caller sees the whole company's catalog or only their
 * own records. Route-model binding does not run under api.json, so every
 * action takes an $id and re-queries with the same tenant scope.
 *
 * ponytail: the web store/update dispatch CreateProductServiceItem +
 * CustomFieldSaved events for custom-field packages; the API omits them (they
 * expect a web request with custom_fields). Add if a package needs API-created
 * items to carry custom fields.
 */
class ProductServiceItemApiController extends Controller
{
    use ApiResponseTrait;

    /** Shared owner scope: whole company, own records, or nothing. */
    private function ownerScope($query)
    {
        return $query->where(function ($q) {
            if (Auth::user()->can('manage-any-product-service-item')) {
                $q->where('created_by', creatorId());
            } elseif (Auth::user()->can('manage-own-product-service-item')) {
                $q->where('creator_id', Auth::id());
            } else {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function index(Request $request)
    {
        try {
            if (!Auth::user()->can('manage-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $items = $this->ownerScope(ProductServiceItem::query())
                ->with(['category:id,name', 'unitRelation:id,unit_name', 'warehouseStocks:id,product_id,quantity'])
                ->when($request->name, fn ($q) => $q->where('name', 'like', '%' . $request->name . '%'))
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
                ->latest()
                ->paginate($request->get('per_page', 10))
                ->withQueryString();

            $items->getCollection()->transform(function ($item) {
                $item->total_quantity = $item->warehouseStocks->sum('quantity');
                return $item;
            });

            return $this->paginatedResponse($items, __('Items retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductServiceItem API index error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function store(StoreProductServiceItemApiRequest $request)
    {
        try {
            if (!Auth::user()->can('create-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $validated = $request->validated();

            $item = new ProductServiceItem();
            $item->name = $validated['name'];
            $item->sku = $validated['sku'];
            $item->type = $validated['type'] ?? null;
            $item->tax_ids = !empty($validated['tax_ids']) ? array_map('intval', $validated['tax_ids']) : null;
            $item->category_id = $validated['category_id'] ?? null;
            $item->unit = $validated['unit'] ?? null;
            $item->description = $validated['description'] ?? null;
            $item->long_description = $validated['long_description'] ?? null;
            $item->sale_price = $validated['sale_price'];
            $item->purchase_price = $validated['purchase_price'];
            $item->image = !empty($validated['image']) ? basename($validated['image']) : null;
            $item->images = !empty($validated['images']) ? array_map('basename', $validated['images']) : null;
            $item->creator_id = Auth::id();
            $item->created_by = creatorId();
            $item->save();

            if (($validated['type'] ?? null) !== 'service' && !empty($validated['warehouse_id'])) {
                WarehouseStock::create([
                    'product_id' => $item->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'quantity' => $validated['quantity'] ?? 0,
                ]);
            }

            return $this->successResponse($item->fresh(), __('Item created successfully'), 201);
        } catch (\Throwable $e) {
            Log::error('ProductServiceItem API store error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function show($id)
    {
        try {
            if (!Auth::user()->can('view-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $item = $this->ownerScope(ProductServiceItem::where('id', $id))
                ->with(['category', 'unitRelation', 'warehouseStocks.warehouse:id,name'])
                ->first();

            if (!$item) {
                return $this->errorResponse(__('Item not found'), null, 404);
            }

            $data = $item->toArray();
            $data['total_quantity'] = $item->warehouseStocks->sum('quantity');
            $data['taxes'] = !empty($item->tax_ids)
                ? ProductServiceTax::whereIn('id', $item->tax_ids)
                    ->where('created_by', creatorId())
                    ->get(['id', 'tax_name', 'rate'])
                : [];

            return $this->successResponse($data, __('Item details retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductServiceItem API show error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function update(UpdateProductServiceItemApiRequest $request, $id)
    {
        try {
            if (!Auth::user()->can('edit-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $item = $this->ownerScope(ProductServiceItem::where('id', $id))->first();

            if (!$item) {
                return $this->errorResponse(__('Item not found'), null, 404);
            }

            $validated = $request->validated();

            $item->name = $validated['name'];
            $item->sku = $validated['sku'];
            $item->type = $validated['type'] ?? null;
            $item->tax_ids = !empty($validated['tax_ids']) ? array_map('intval', $validated['tax_ids']) : null;
            $item->category_id = $validated['category_id'] ?? null;
            $item->unit = $validated['unit'] ?? null;
            $item->description = $validated['description'] ?? null;
            $item->long_description = $validated['long_description'] ?? null;
            $item->sale_price = $validated['sale_price'];
            $item->purchase_price = $validated['purchase_price'];
            if (array_key_exists('image', $validated)) {
                $item->image = !empty($validated['image']) ? basename($validated['image']) : null;
            }
            if (array_key_exists('images', $validated)) {
                $item->images = !empty($validated['images']) ? array_map('basename', $validated['images']) : null;
            }
            $item->save();

            if (isset($validated['quantity'])) {
                $stock = $item->warehouseStocks()->first();
                if ($stock) {
                    $stock->update(['quantity' => $validated['quantity']]);
                }
            }

            return $this->successResponse($item->fresh(), __('Item updated successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductServiceItem API update error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::user()->can('delete-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $item = $this->ownerScope(ProductServiceItem::where('id', $id))->first();

            if (!$item) {
                return $this->errorResponse(__('Item not found'), null, 404);
            }

            WarehouseStock::where('product_id', $item->id)->delete();
            $item->delete();

            return $this->successResponse(null, __('Item deleted successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductServiceItem API destroy error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }
}
