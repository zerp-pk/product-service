<?php

namespace Zerp\ProductService\Listeners;

use Zerp\ProductService\Models\WarehouseStock;
use Zerp\Retainer\Events\ConvertSalesRetainer;

/**
 * Converting a sales retainer produces a sales invoice, so the goods leave the
 * warehouse exactly as they do when an invoice is posted directly.
 *
 * This event was previously handled by CompleteSalesReturnListener, which puts stock
 * back in: the opposite movement, reading fields a retainer conversion does not carry.
 * See zerp-pk/product-service#4.
 *
 * The event lives in the retainer module, which is not released yet, so nothing
 * dispatches this today. The accounting module pairs the same event with its own
 * listener and reads $event->invoice, which is the contract followed here.
 */
class ConvertSalesRetainerListener
{
    public function handle(ConvertSalesRetainer $event)
    {
        WarehouseStock::deductForSalesInvoice($event->invoice);
    }
}
