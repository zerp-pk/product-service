<?php

namespace Zerp\ProductService\Listeners;

use App\Events\PostSalesInvoice;
use Zerp\ProductService\Models\WarehouseStock;

class PostSalesInvoiceListener
{
    public function handle(PostSalesInvoice $event)
    {
        WarehouseStock::deductForSalesInvoice($event->salesInvoice);
    }
}
