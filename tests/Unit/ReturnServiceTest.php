<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReturnService::class);
    }

    private function createPurchaseReturnRecord(Product $product, int $qty, ?int $batchId, float $buyPrice = 1000): PurchaseReturn
    {
        $supplier = Supplier::factory()->create();
        $purchase = Purchase::create([
            'invoice_number' => 'INV-PR-' . uniqid(),
            'supplier_id' => $supplier->id,
            'status' => 'closed',
            'purchase_date' => now(),
            'total_amount' => $qty * $buyPrice,
        ]);
        $user = User::factory()->create();

        $return = PurchaseReturn::create([
            'return_number' => 'PR-' . uniqid(),
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'return_date' => now(),
            'total_amount' => $qty * $buyPrice,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'product_id' => $product->id,
            'inventory_batch_id' => $batchId,
            'qty' => $qty,
            'buy_price' => $buyPrice,
            'subtotal' => $qty * $buyPrice,
        ]);

        return $return;
    }

    private function createSalesReturnRecord(Product $product, int $qty, ?int $batchId, float $buyPrice = 1000): SalesReturn
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        $sale = Sale::create([
            'receipt_number' => 'REC-' . uniqid(),
            'customer_id' => $customer->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal_amount' => $qty * 1500,
            'grand_total' => $qty * 1500,
        ]);
        $user = User::factory()->create();

        $return = SalesReturn::create([
            'return_number' => 'SR-' . uniqid(),
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'return_date' => now(),
            'total_amount' => $qty * $buyPrice,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'product_id' => $product->id,
            'inventory_batch_id' => $batchId,
            'qty' => $qty,
            'buy_price' => $buyPrice,
            'selling_price' => 1500,
            'subtotal' => $qty * 1500,
        ]);

        return $return;
    }

    public function test_purchase_return_deducts_stock_from_specified_batch(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 20]);
        $batch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 20,
        ]);

        $return = $this->createPurchaseReturnRecord($product, 5, $batch->id);
        $this->service->processPurchaseReturn($return->fresh());

        $this->assertSame(15, (int) $batch->fresh()->current_qty);
        $this->assertSame(15, (int) $product->fresh()->total_stock);
        $this->assertSame('approved', $return->fresh()->status);
    }

    public function test_purchase_return_uses_fifo_when_no_batch_specified(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 30]);

        $oldBatch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 10,
            'created_at' => now()->subDays(2),
        ]);
        $newBatch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 20,
            'created_at' => now()->subDay(),
        ]);

        $return = $this->createPurchaseReturnRecord($product, 15, null);
        $this->service->processPurchaseReturn($return->fresh());

        // FIFO: 10 from old, 5 from new
        $this->assertSame(0, (int) $oldBatch->fresh()->current_qty);
        $this->assertSame(15, (int) $newBatch->fresh()->current_qty);
        $this->assertSame(15, (int) $product->fresh()->total_stock);
    }

    public function test_purchase_return_throws_on_insufficient_stock(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 5]);
        $batch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 5,
        ]);

        $return = $this->createPurchaseReturnRecord($product, 10, $batch->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->processPurchaseReturn($return->fresh());
    }

    public function test_sales_return_restores_stock_to_specified_batch(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 10]);
        $batch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 10,
        ]);

        $return = $this->createSalesReturnRecord($product, 3, $batch->id);
        $this->service->processSalesReturn($return->fresh());

        $this->assertSame(13, (int) $batch->fresh()->current_qty);
        $this->assertSame(13, (int) $product->fresh()->total_stock);
        $this->assertSame('approved', $return->fresh()->status);
    }

    public function test_sales_return_creates_new_batch_when_no_existing_batch(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 0]);

        $return = $this->createSalesReturnRecord($product, 5, null);
        $this->service->processSalesReturn($return->fresh());

        $batch = InventoryBatch::where('product_id', $product->id)->first();
        $this->assertNotNull($batch);
        $this->assertSame(5, (int) $batch->current_qty);
        $this->assertSame(5, (int) $product->fresh()->total_stock);
    }
}
