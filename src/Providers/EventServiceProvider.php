<?php

namespace Zerp\ProductService\Providers;

use App\Events\PostPurchaseInvoice;
use App\Events\ApprovePurchaseReturn;
use App\Events\CompleteSalesReturn;
use App\Events\PostSalesInvoice;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Zerp\Pos\Events\CreatePos;
use Zerp\ProductService\Listeners\PostPurchaseInvoiceListener;
use Zerp\ProductService\Listeners\ApprovePurchaseReturnListener;
use Zerp\ProductService\Listeners\CompleteSalesReturnListener;
use Zerp\ProductService\Listeners\PosCreateListener;
use Zerp\ProductService\Listeners\PostSalesInvoiceListener;
use Workdo\Retainer\Events\ConvertSalesRetainer;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PostPurchaseInvoice::class => [
            PostPurchaseInvoiceListener::class,
        ],
        PostSalesInvoice::class => [
            PostSalesInvoiceListener::class,
        ],
        ApprovePurchaseReturn::class => [
            ApprovePurchaseReturnListener::class,
        ],
        CompleteSalesReturn::class => [
            CompleteSalesReturnListener::class,
        ],
        CreatePos::class => [
            PosCreateListener::class,
        ],
        ConvertSalesRetainer::class => [
            CompleteSalesReturnListener::class,
        ],
    ];
}
