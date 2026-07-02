<?php

namespace Zerp\ProductService\Listeners;

use Zerp\Pos\Events\CreatePos;
use Zerp\ProductService\Models\WarehouseStock;

class PosCreateListener
{
    public function handle(CreatePos $event)
    {
        $posSale = $event->posSale;
        foreach ($posSale->items()->get() as $item) {
            $stock = WarehouseStock::where('warehouse_id', $posSale->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($stock) {
                $stock->decrement('quantity', $item->quantity);
            }
        }
    }
}
