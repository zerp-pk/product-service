<?php

namespace Zerp\ProductService\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id', 
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }

    /**
     * Take an invoice's goods out of its warehouse.
     *
     * Lives here rather than in a listener because more than one event results in
     * this same movement: posting a sales invoice, and converting a sales retainer
     * into one. Keeping a single copy is deliberate. The bug this was written for
     * came from a listener being reused for an event it did not belong to.
     */
    public static function deductForSalesInvoice($salesInvoice): void
    {
        if ($salesInvoice->type !== 'product') {
            return;
        }

        foreach ($salesInvoice->items()->get() as $item) {
            $stock = static::where('warehouse_id', $salesInvoice->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();

            if ($stock) {
                $stock->decrement('quantity', $item->quantity);
            }
        }
    }

    public static function available(int $productId, ?int $warehouseId): float
    {
        if (!$warehouseId) {
            return 0;
        }

        return (float) (static::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0);
    }
}