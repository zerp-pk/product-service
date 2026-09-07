<?php

namespace Zerp\ProductService\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Zerp\ProductService\Listeners\CompleteSalesReturnListener;
use Zerp\ProductService\Listeners\ConvertSalesRetainerListener;
use Zerp\ProductService\Listeners\PostSalesInvoiceListener;
use Zerp\ProductService\Models\WarehouseStock;
use Zerp\ProductService\Providers\EventServiceProvider;

/**
 * ConvertSalesRetainer was mapped to CompleteSalesReturnListener, which moves stock
 * the opposite way: a retainer conversion would have put goods back into the warehouse
 * instead of taking them out. See zerp-pk/product-service#4.
 *
 * The retainer module is not released, so nothing dispatches the event yet. These
 * pin the wiring and the direction of the movement before it starts firing.
 */
class StockMovementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Stands in for the app's SalesInvoice: the listeners only reach for the
     * warehouse, the type, and the line items.
     */
    private function invoice(string $type = 'product'): object
    {
        return new class($type) {
            public $warehouse_id = 1;
            public $type;

            public function __construct($type)
            {
                $this->type = $type;
            }

            public function items()
            {
                return new class {
                    public function get()
                    {
                        return collect([(object) ['product_id' => 7, 'quantity' => 3]]);
                    }
                };
            }
        };
    }

    private function stock(): float
    {
        return (float) DB::table('warehouse_stocks')->where('product_id', 7)->value('quantity');
    }

    private function seedStock(float $quantity = 10): void
    {
        WarehouseStock::create(['warehouse_id' => 1, 'product_id' => 7, 'quantity' => $quantity]);
    }

    public function test_the_retainer_event_is_wired_to_its_own_listener(): void
    {
        $listen = (new \ReflectionClass(EventServiceProvider::class))
            ->getDefaultProperties()['listen'];

        $this->assertSame(
            [ConvertSalesRetainerListener::class],
            $listen[\Zerp\Retainer\Events\ConvertSalesRetainer::class],
            'a retainer conversion must not be handled by the sales return listener'
        );

        $this->assertNotContains(
            CompleteSalesReturnListener::class,
            $listen[\Zerp\Retainer\Events\ConvertSalesRetainer::class]
        );
    }

    public function test_converting_a_retainer_takes_stock_out_of_the_warehouse(): void
    {
        $this->seedStock(10);

        (new ConvertSalesRetainerListener())->handle(
            new \Zerp\Retainer\Events\ConvertSalesRetainer($this->invoice())
        );

        $this->assertSame(7.0, $this->stock(), 'stock must fall, not rise');
    }

    public function test_posting_an_invoice_moves_stock_the_same_way(): void
    {
        $this->seedStock(10);

        WarehouseStock::deductForSalesInvoice($this->invoice());

        $this->assertSame(7.0, $this->stock());
    }

    public function test_a_service_invoice_leaves_stock_alone(): void
    {
        $this->seedStock(10);

        WarehouseStock::deductForSalesInvoice($this->invoice('service'));

        $this->assertSame(10.0, $this->stock());
    }
}
