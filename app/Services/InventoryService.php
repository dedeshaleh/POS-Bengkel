<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function receive(Product $product, int $qtyInBaseUom, float $baseUomBuyPrice, int $purchaseItemId, ?int $warehouseId = null, ?string $expiredDate = null): InventoryBatch
    {
        return DB::transaction(function () use ($product, $qtyInBaseUom, $baseUomBuyPrice, $purchaseItemId, $warehouseId, $expiredDate) {
            $batch = InventoryBatch::create([
                'product_id' => $product->id,
                'purchase_item_id' => $purchaseItemId,
                'warehouse_id' => $warehouseId,
                'base_uom_buy_price' => $baseUomBuyPrice,
                'expired_date' => $expiredDate,
                'initial_qty' => $qtyInBaseUom,
                'current_qty' => $qtyInBaseUom,
            ]);

            $this->refreshProductStock($product);

            return $batch;
        });
    }

    /**
     * Deduct stock using FIFO. Returns the batch splits used for sale_items.
     */
    public function lockForSale(Product $product, int $qty): array
    {
        if ($product->is_bundle) {
            return $this->lockBundle($product, $qty);
        }

        return $this->lockPhysicalProduct($product, $qty);
    }

    private function lockBundle(Product $bundle, int $qty): array
    {
        $lines = [];
        $bundle->loadMissing('bundleItems.component');

        if ($bundle->bundleItems->isEmpty()) {
            throw new RuntimeException("Bundle {$bundle->sku} has no components.");
        }

        foreach ($bundle->bundleItems as $item) {
            $lines = array_merge(
                $lines,
                $this->lockPhysicalProduct($item->component, $item->qty * $qty)
            );
        }

        return $lines;
    }

    private function lockPhysicalProduct(Product $product, int $qty): array
    {
        return DB::transaction(function () use ($product, $qty) {
            $remaining = $qty;
            $lines = [];

            $batches = InventoryBatch::query()
                ->where('product_id', $product->id)
                ->where('current_qty', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, $batch->current_qty);
                $batch->decrement('current_qty', $deduct);
                $remaining -= $deduct;

                $lines[] = [
                    'product_id' => $product->id,
                    'inventory_batch_id' => $batch->id,
                    'qty' => $deduct,
                    'buy_price' => (float) $batch->base_uom_buy_price,
                ];
            }

            if ($remaining > 0) {
                throw new RuntimeException("Insufficient stock for {$product->sku}. Need {$qty}, short {$remaining}.");
            }

            $this->refreshProductStock($product);

            return $lines;
        });
    }

    public function refreshProductStock(Product $product): void
    {
        $product->update([
            'total_stock' => InventoryBatch::where('product_id', $product->id)->sum('current_qty'),
        ]);
    }
}
