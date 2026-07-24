<?php

namespace Zerp\ProductService\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

/** Body for creating/updating a product-service unit. */
class UnitApiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'unit_name' => 'required|string|max:255',
        ];
    }
}
