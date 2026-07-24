<?php

namespace Zerp\ProductService\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Zerp\ProductService\Http\Requests\Api\CategoryApiRequest;
use Zerp\ProductService\Models\ProductServiceCategory;

/** REST API for product-service categories. See ProductServiceItemApiController for the pattern. */
class CategoryApiController extends Controller
{
    use ApiResponseTrait;

    private function ownerScope($query)
    {
        return $query->where(function ($q) {
            if (Auth::user()->can('manage-any-product-service-categories')) {
                $q->where('created_by', creatorId());
            } elseif (Auth::user()->can('manage-own-product-service-categories')) {
                $q->where('creator_id', Auth::id());
            } else {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function index(Request $request)
    {
        try {
            if (!Auth::user()->can('manage-product-service-categories')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $categories = $this->ownerScope(ProductServiceCategory::query())
                ->when($request->name, fn ($q) => $q->where('name', 'like', '%' . $request->name . '%'))
                ->latest()
                ->paginate($request->get('per_page', 10))
                ->withQueryString();

            return $this->paginatedResponse($categories, __('Categories retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Category API index error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function store(CategoryApiRequest $request)
    {
        try {
            if (!Auth::user()->can('create-product-service-categories')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $category = ProductServiceCategory::create($request->validated() + [
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            return $this->successResponse($category, __('Category created successfully'), 201);
        } catch (\Throwable $e) {
            Log::error('ProductService Category API store error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function show($id)
    {
        try {
            if (!Auth::user()->can('manage-product-service-categories')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $category = $this->ownerScope(ProductServiceCategory::where('id', $id))->first();

            if (!$category) {
                return $this->errorResponse(__('Category not found'), null, 404);
            }

            return $this->successResponse($category, __('Category details retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Category API show error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function update(CategoryApiRequest $request, $id)
    {
        try {
            if (!Auth::user()->can('edit-product-service-categories')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $category = $this->ownerScope(ProductServiceCategory::where('id', $id))->first();

            if (!$category) {
                return $this->errorResponse(__('Category not found'), null, 404);
            }

            $category->update($request->validated());

            return $this->successResponse($category, __('Category updated successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Category API update error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::user()->can('delete-product-service-categories')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $category = $this->ownerScope(ProductServiceCategory::where('id', $id))->first();

            if (!$category) {
                return $this->errorResponse(__('Category not found'), null, 404);
            }

            $category->delete();

            return $this->successResponse(null, __('Category deleted successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Category API destroy error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }
}
