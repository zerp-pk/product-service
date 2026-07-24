<?php

namespace Zerp\ProductService\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Zerp\ProductService\Http\Requests\Api\UnitApiRequest;
use Zerp\ProductService\Models\ProductServiceUnit;

/** REST API for product-service units. See ProductServiceItemApiController for the pattern. */
class UnitApiController extends Controller
{
    use ApiResponseTrait;

    private function ownerScope($query)
    {
        return $query->where(function ($q) {
            if (Auth::user()->can('manage-any-product-service-units')) {
                $q->where('created_by', creatorId());
            } elseif (Auth::user()->can('manage-own-product-service-units')) {
                $q->where('creator_id', Auth::id());
            } else {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function index(Request $request)
    {
        try {
            if (!Auth::user()->can('manage-product-service-units')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $units = $this->ownerScope(ProductServiceUnit::query())
                ->when($request->name, fn ($q) => $q->where('unit_name', 'like', '%' . $request->name . '%'))
                ->latest()
                ->paginate($request->get('per_page', 10))
                ->withQueryString();

            return $this->paginatedResponse($units, __('Units retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Unit API index error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function store(UnitApiRequest $request)
    {
        try {
            if (!Auth::user()->can('create-product-service-units')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $unit = ProductServiceUnit::create($request->validated() + [
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            return $this->successResponse($unit, __('Unit created successfully'), 201);
        } catch (\Throwable $e) {
            Log::error('ProductService Unit API store error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function show($id)
    {
        try {
            if (!Auth::user()->can('manage-product-service-units')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $unit = $this->ownerScope(ProductServiceUnit::where('id', $id))->first();

            if (!$unit) {
                return $this->errorResponse(__('Unit not found'), null, 404);
            }

            return $this->successResponse($unit, __('Unit details retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Unit API show error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function update(UnitApiRequest $request, $id)
    {
        try {
            if (!Auth::user()->can('edit-product-service-units')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $unit = $this->ownerScope(ProductServiceUnit::where('id', $id))->first();

            if (!$unit) {
                return $this->errorResponse(__('Unit not found'), null, 404);
            }

            $unit->update($request->validated());

            return $this->successResponse($unit, __('Unit updated successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Unit API update error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::user()->can('delete-product-service-units')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $unit = $this->ownerScope(ProductServiceUnit::where('id', $id))->first();

            if (!$unit) {
                return $this->errorResponse(__('Unit not found'), null, 404);
            }

            $unit->delete();

            return $this->successResponse(null, __('Unit deleted successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Unit API destroy error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }
}
