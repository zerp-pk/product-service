<?php

namespace Zerp\ProductService\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Zerp\ProductService\Models\ProductServiceTax;

class DestroyProductServiceTax
{
    use Dispatchable;

    public function __construct(
        public ProductServiceTax $tax,
    ) {}
}
