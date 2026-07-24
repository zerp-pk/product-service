<?php

namespace Zerp\ProductService\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

/** Body for creating/updating a product-service tax. */
class TaxApiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'tax_name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ];
    }
}
