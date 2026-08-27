<?php

namespace Tests\Unit;

use App\Models\BundleItem;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    public function test_lock_for_sale_deducts_oldest_batch_first(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);

        // Create 3 batches with different timestamps and prices
        $oldBatch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'base_uom_buy_price' => 1000,
            'current_qty' => 10,
            'created_at' => now()->subDays(3),
        ]);
        $midBatch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'base_uom_buy_price' => 2000,
            'current_qty' => 10,
            'created_at' => now()->subDays(2),
        ]);
        $newBatch = InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'base_uom_buy_price' => 3000,
            'current_qty' => 10,
            'created_at' => now()->subDay(),
        ]);

        $product->update(['total_stock' => 30]);

        // Lock 15 units — should take 10 from old, 5 from mid
        $lines = $this->service->lockForSale($product, 15);

        $this->assertCount(2, $lines);
        $this->assertEquals($oldBatch->id, $lines[0]['inventory_batch_id']);
        $this->assertSame(10, $lines[0]['qty']);
        $this->assertEquals($midBatch->id, $lines[1]['inventory_batch_id']);
        $this->assertSame(5, $lines[1]['qty']);

        // Verify batch quantities updated
        $this->assertSame(0, (int) $oldBatch->fresh()->current_qty);
        $this->assertSame(5, (int) $midBatch->fresh()->current_qty);
        $this->assertSame(10, (int) $newBatch->fresh()->current_qty);

        // Verify total_stock refreshed
        $this->assertSame(15, (int) $product->fresh()->total_stock);
    }

    public function test_lock_for_sale_throws_on_insufficient_stock(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);
        InventoryBatch::factory()->create([
            'product_id' => $product->id,
            'current_qty' => 5,
        ]);
        $product->update(['total_stock' => 5]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->lockForSale($product, 10);
    }

    public function test_lock_for_sale_bundle_deducts_component_stock(): void
    {
        // Create component products with stock
        $componentA = Product::factory()->create(['base_uom_code' => 'PCS']);
        $componentB = Product::factory()->create(['base_uom_code' => 'PCS']);

        InventoryBatch::factory()->create([
            'product_id' => $componentA->id,
            'current_qty' => 20,
            'created_at' => now()->subDay(),
        ]);
        InventoryBatch::factory()->create([
            'product_id' => $componentB->id,
            'current_qty' => 20,
            'created_at' => now()->subDay(),
        ]);

        $componentA->update(['total_stock' => 20]);
        $componentB->update(['total_stock' => 20]);

        // Create bundle product
        $bundle = Product::factory()->create([
            'is_bundle' => true,
            'base_uom_code' => 'SET',
        ]);

        // Bundle = 2x componentA + 1x componentB
        BundleItem::create([
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $componentA->id,
            'qty' => 2,
        ]);
        BundleItem::create([
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $componentB->id,
            'qty' => 1,
        ]);

        // Sell 3 bundles — needs 6x A and 3x B
        $lines = $this->service->lockForSale($bundle, 3);

        // Should have lines for both components
        $componentALines = array_filter($lines, fn ($l) => $l['product_id'] === $componentA->id);
        $componentBLines = array_filter($lines, fn ($l) => $l['product_id'] === $componentB->id);

        $this->assertNotEmpty($componentALines);
        $this->assertNotEmpty($componentBLines);

        $totalA = array_sum(array_map(fn ($l) => $l['qty'], $componentALines));
        $totalB = array_sum(array_map(fn ($l) => $l['qty'], $componentBLines));

        $this->assertSame(6, $totalA); // 2 * 3 bundles
        $this->assertSame(3, $totalB); // 1 * 3 bundles

        // Verify stock deducted
        $this->assertSame(14, (int) $componentA->fresh()->total_stock);
        $this->assertSame(17, (int) $componentB->fresh()->total_stock);
    }

    public function test_receive_creates_batch_and_updates_stock(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS', 'total_stock' => 0]);

        $purchase = Purchase::create([
            'invoice_number' => 'INV-TEST-001',
            'supplier_id' => \App\Models\Supplier::factory()->create()->id,
            'status' => 'on_order',
            'purchase_date' => now(),
            'total_amount' => 250000,
        ]);
        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'purchased_uom_code' => 'PCS',
            'purchased_qty' => 50,
            'qty_in_base_uom' => 50,
            'buy_price_per_purchased_uom' => 5000,
            'subtotal' => 250000,
        ]);

        $batch = $this->service->receive($product, 50, 5000.00, $purchaseItem->id);

        $this->assertSame(50, (int) $batch->current_qty);
        $this->assertSame(50, (int) $batch->initial_qty);
        $this->assertEquals('5000.00', (string) $batch->base_uom_buy_price);
        $this->assertSame(50, (int) $product->fresh()->total_stock);
    }

    public function test_lock_for_sale_empty_bundle_throws(): void
    {
        $bundle = Product::factory()->create(['is_bundle' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has no components');

        $this->service->lockForSale($bundle, 1);
    }
}
