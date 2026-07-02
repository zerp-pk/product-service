<?php

namespace Zerp\ProductService\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Zerp\ProductService\Models\ProductServiceCategory;

class DestroyProductServiceCategory
{
    use Dispatchable;

    public function __construct(
        public ProductServiceCategory $itemCategory,
    ) {}
}
