<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockAdjustmentService
{
    public function calculateDifference(int $expectedQty, int $actualQty): int
    {
        return $actualQty - $expectedQty;
    }

    public function finalize(StockAdjustment $stockAdjustment): StockAdjustment
    {
        if ($stockAdjustment->status !== 'draft') {
            throw new RuntimeException('Only draft adjustments can be finalized.');
        }

        return DB::transaction(function () use ($stockAdjustment) {
            $stockAdjustment->load('items.product', 'items.inventoryBatch');

            foreach ($stockAdjustment->items as $item) {
                $this->applyAdjustment($item);
            }

            $stockAdjustment->update(['status' => 'finalized']);

            return $stockAdjustment;
        });
    }

    private function applyAdjustment(StockAdjustmentItem $item): void
    {
        if ($item->inventory_batch_id !== null) {
            $batch = InventoryBatch::lockForUpdate()->findOrFail($item->inventory_batch_id);
            $newQty = $batch->current_qty + $item->difference;

            if ($newQty < 0) {
                throw new RuntimeException("Batch stock cannot be negative for product {$item->product->sku}.");
            }

            $batch->update(['current_qty' => $newQty]);
            $this->refreshProductStock($item->product);

            return;
        }

        $product = Product::lockForUpdate()->findOrFail($item->product_id);
        $newStock = $product->total_stock + $item->difference;

        if ($newStock < 0) {
            throw new RuntimeException("Product stock cannot be negative for {$product->sku}.");
        }

        $product->update(['total_stock' => $newStock]);
    }

    private function refreshProductStock(Product $product): void
    {
        $product->update([
            'total_stock' => InventoryBatch::where('product_id', $product->id)->sum('current_qty'),
        ]);
    }
}
