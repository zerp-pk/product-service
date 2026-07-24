<?php

namespace Zerp\ProductService\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

/** Body for creating/updating a product-service category. */
class CategoryApiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:32',
        ];
    }
}
