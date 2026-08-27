<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseTransferService
{
    public function generateTransferNumber(): string
    {
        $date = now()->format('Ymd');
        $last = WarehouseTransfer::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->value('id') ?? 0;

        return "TF-{$date}-" . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    public function finalize(WarehouseTransfer $transfer): WarehouseTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new RuntimeException('Only draft transfers can be finalized.');
        }

        if ($transfer->from_warehouse_id === $transfer->to_warehouse_id) {
            throw new RuntimeException('Source and destination warehouse cannot be the same.');
        }

        return DB::transaction(function () use ($transfer) {
            $transfer->load('items.product');

            foreach ($transfer->items as $item) {
                $this->transferItem($transfer, $item);
            }

            $transfer->update(['status' => 'completed']);

            return $transfer;
        });
    }

    private function transferItem(WarehouseTransfer $transfer, $item): void
    {
        $remaining = (int) $item->qty;

        if ($remaining <= 0) {
            return;
        }

        if ($item->inventory_batch_id !== null) {
            $sourceBatch = InventoryBatch::lockForUpdate()
                ->where('id', $item->inventory_batch_id)
                ->where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('current_qty', '>', 0)
                ->first();

            if ($sourceBatch === null) {
                throw new RuntimeException("Batch not available for product {$item->product->sku} in source warehouse.");
            }

            $deduct = min($remaining, $sourceBatch->current_qty);
            $sourceBatch->decrement('current_qty', $deduct);
            $this->createTargetBatch($transfer, $sourceBatch, $deduct);
            $remaining -= $deduct;

            if ($remaining > 0) {
                throw new RuntimeException("Insufficient stock in selected batch for product {$item->product->sku}. Short {$remaining}.");
            }

            $this->refreshProductStock($item->product);

            return;
        }

        $batches = InventoryBatch::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $transfer->from_warehouse_id)
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
            $this->createTargetBatch($transfer, $batch, $deduct);
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new RuntimeException("Insufficient stock for product {$item->product->sku} in source warehouse. Need {$item->qty}, short {$remaining}.");
        }

        $this->refreshProductStock($item->product);
    }

    private function createTargetBatch(WarehouseTransfer $transfer, InventoryBatch $sourceBatch, int $qty): void
    {
        InventoryBatch::create([
            'product_id' => $sourceBatch->product_id,
            'purchase_item_id' => $sourceBatch->purchase_item_id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'base_uom_buy_price' => $sourceBatch->base_uom_buy_price,
            'expired_date' => $sourceBatch->expired_date,
            'initial_qty' => $qty,
            'current_qty' => $qty,
        ]);
    }

    private function refreshProductStock(Product $product): void
    {
        $product->update([
            'total_stock' => InventoryBatch::where('product_id', $product->id)->sum('current_qty'),
        ]);
    }
}
