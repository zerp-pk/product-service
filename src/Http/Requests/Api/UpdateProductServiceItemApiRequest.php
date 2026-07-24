<?php

namespace Zerp\ProductService\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

/**
 * Body for PUT/PATCH /api/product-service-catalog/items/{id}. Same shape as
 * the store request; quantity updates the item's existing warehouse stock.
 */
class UpdateProductServiceItemApiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $creatorId = creatorId();

        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'tax_ids' => 'nullable|array',
            'tax_ids.*' => 'integer|exists:product_service_taxes,id,created_by,' . $creatorId,
            'category_id' => 'nullable|exists:product_service_categories,id,created_by,' . $creatorId,
            'unit' => 'nullable|exists:product_service_units,id,created_by,' . $creatorId,
            'description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'quantity' => 'nullable|integer|min:0',
        ];
    }
}
