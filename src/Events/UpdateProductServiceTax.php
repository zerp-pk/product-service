<?php

namespace Zerp\ProductService\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Zerp\ProductService\Models\ProductServiceTax;

class UpdateProductServiceTax
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public ProductServiceTax $tax
    ) {}
}
