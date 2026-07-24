<?php

namespace Zerp\ProductService\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Zerp\ProductService\Models\ProductServiceCategory;
use Zerp\ProductService\Models\ProductServiceItem;
use Zerp\ProductService\Models\ProductServiceUnit;

/** Summary counts for the Product & Service catalog, scoped to the company. */
class DashboardApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (!Auth::user()->can('manage-product-service-item')) {
                return $this->errorResponse(__('Permission denied'), null, 403);
            }

            $creatorId = creatorId();

            $stats = [
                'total_items' => ProductServiceItem::where('created_by', $creatorId)->count(),
                'total_products' => ProductServiceItem::where('created_by', $creatorId)->where('type', '!=', 'service')->count(),
                'total_services' => ProductServiceItem::where('created_by', $creatorId)->where('type', 'service')->count(),
                'total_categories' => ProductServiceCategory::where('created_by', $creatorId)->count(),
                'total_units' => ProductServiceUnit::where('created_by', $creatorId)->count(),
            ];

            $recentItems = ProductServiceItem::where('created_by', $creatorId)
                ->with(['category:id,name'])
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'sku', 'sale_price', 'type', 'category_id', 'created_at']);

            return $this->successResponse([
                'stats' => $stats,
                'recent_items' => $recentItems,
            ], __('Dashboard retrieved successfully'));
        } catch (\Throwable $e) {
            Log::error('ProductService Dashboard API error', ['e' => $e]);
            return $this->errorResponse(__('Something went wrong'), null, 500);
        }
    }
}
