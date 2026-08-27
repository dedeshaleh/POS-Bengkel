<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handle stock movements for purchase and sales returns.
 *
 * Purchase Return (Retur Pembelian):
 *   Stock OUT — decrement inventory_batches.current_qty from the
 *   specified batch (or oldest batch if not specified).
 *
 * Sales Return (Retur Penjualan):
 *   Stock IN — increment inventory_batches.current_qty on the
 *   specified batch (or create a new batch if none specified).
 */
class ReturnService
{
    /**
     * Process an approved purchase return: deduct stock from batches.
     */
    public function processPurchaseReturn(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                $product = $item->product;
                $qty = $item->qty;

                if ($item->inventory_batch_id) {
                    $batch = InventoryBatch::lockForUpdate()->find($item->inventory_batch_id);
                    if (! $batch || $batch->current_qty < $qty) {
                        throw new RuntimeException(
                            "Insufficient stock in batch #{$item->inventory_batch_id} for {$product->sku}."
                        );
                    }
                    $batch->decrement('current_qty', $qty);
                } else {
                    // Deduct from oldest batches (FIFO) until qty fulfilled.
                    $remaining = $qty;
                    $batches = InventoryBatch::where('product_id', $product->id)
                        ->where('current_qty', '>', 0)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $take = min($remaining, $batch->current_qty);
                        $batch->decrement('current_qty', $take);
                        $remaining -= $take;
                    }

                    if ($remaining > 0) {
                        throw new RuntimeException(
                            "Insufficient stock for {$product->sku}. Need {$qty}, short {$remaining}."
                        );
                    }
                }

                $product->decrement('total_stock', $qty);
            }

            $return->update(['status' => 'approved']);
        });
    }

    /**
     * Process an approved sales return: restore stock to batches.
     */
    public function processSalesReturn(SalesReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                $product = $item->product;
                $qty = $item->qty;

                if ($item->inventory_batch_id) {
                    // Restore to the specified batch.
                    $batch = InventoryBatch::find($item->inventory_batch_id);
                    if ($batch) {
                        $batch->increment('current_qty', $qty);
                    }
                } else {
                    // Restore to the newest batch if available, otherwise
                    // create a new batch with the sale's buy price.
                    $batch = InventoryBatch::where('product_id', $product->id)
                        ->latest('created_at')
                        ->first();

                    if ($batch) {
                        $batch->increment('current_qty', $qty);
                    } else {
                        InventoryBatch::create([
                            'product_id' => $product->id,
                            'purchase_item_id' => null,
                            'warehouse_id' => null,
                            'base_uom_buy_price' => $item->buy_price,
                            'initial_qty' => $qty,
                            'current_qty' => $qty,
                        ]);
                    }
                }

                $product->increment('total_stock', $qty);
            }

            $return->update(['status' => 'approved']);
        });
    }
}
