<?php

namespace Zerp\ProductService\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Zerp\ProductService\Http\Requests\Api\TaxApiRequest;
use Zerp\ProductService\Models\ProductServiceTax;

/** REST API for product-service taxes. See ProductServiceItemApiController for the pattern. */
class TaxApiController extends Controller
{
    use ApiResponseTrait;

    private function ownerScope($query)
    {
        return $query->where(function ($q) {
            if (Auth::user()->can('manage-any-product-service-taxes')) {
                $q->where('created_by', creatorId());
            } elseif (Auth::user()->can('manage-own-product-service-taxes')) {
                $q->where('creator_id', Auth::id());
            } else {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function index(Request $request)
    {
        try {
            if (!Auth::user()->can('manage-product-service-taxes')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $taxes = $this->ownerScope(ProductServiceTax::query())
                ->when($request->name, fn ($q) => $q->where('tax_name', 'like', '%' . $request->name . '%'))
                ->latest()
                ->paginate($request->get('per_page', 10))
                ->withQueryString();

            return $this->paginatedResponse($taxes, __('Taxes retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Tax API index error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function store(TaxApiRequest $request)
    {
        try {
            if (!Auth::user()->can('create-product-service-taxes')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $tax = ProductServiceTax::create($request->validated() + [
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            return $this->successResponse($tax, __('Tax created successfully'), 201);
        } catch (\Throwable $e) {
            Log::error('ProductService Tax API store error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function show($id)
    {
        try {
            if (!Auth::user()->can('manage-product-service-taxes')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $tax = $this->ownerScope(ProductServiceTax::where('id', $id))->first();

            if (!$tax) {
                return $this->errorResponse(__('Tax not found'), null, 404);
            }

            return $this->successResponse($tax, __('Tax details retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Tax API show error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function update(TaxApiRequest $request, $id)
    {
        try {
            if (!Auth::user()->can('edit-product-service-taxes')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $tax = $this->ownerScope(ProductServiceTax::where('id', $id))->first();

            if (!$tax) {
                return $this->errorResponse(__('Tax not found'), null, 404);
            }

            $tax->update($request->validated());

            return $this->successResponse($tax, __('Tax updated successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Tax API update error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::user()->can('delete-product-service-taxes')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $tax = $this->ownerScope(ProductServiceTax::where('id', $id))->first();

            if (!$tax) {
                return $this->errorResponse(__('Tax not found'), null, 404);
            }

            $tax->delete();

            return $this->successResponse(null, __('Tax deleted successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Tax API destroy error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }
}
