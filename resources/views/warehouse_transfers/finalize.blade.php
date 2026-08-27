@extends('layouts.app')

@section('title', 'Finalize Warehouse Transfer')
@section('subtitle', 'Confirm warehouse transfer posting.')

@section('content')
<section class="panel" style="max-width:1100px">
    <h2 style="margin-top:0">Finalize Warehouse Transfer</h2>

    <div class="notice" style="border-color:#f59e0b;background:#fffbeb;margin-bottom:16px">
        <strong>Warning:</strong> Finalizing will move stock from <strong>{{ $warehouseTransfer->fromWarehouse?->name ?? '-' }}</strong> to <strong>{{ $warehouseTransfer->toWarehouse?->name ?? '-' }}</strong> and cannot be undone.
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:16px">
        <div>
            <div class="muted" style="font-size:12px">Transfer Number</div>
            <div style="font-weight:750">{{ $warehouseTransfer->transfer_number }}</div>
        </div>
        <div>
            <div class="muted" style="font-size:12px">Transfer Date</div>
            <div style="font-weight:750">{{ $warehouseTransfer->transfer_date?->format('d M Y') ?? '-' }}</div>
        </div>
        <div>
            <div class="muted" style="font-size:12px">From Warehouse</div>
            <div style="font-weight:750">{{ $warehouseTransfer->fromWarehouse?->name ?? '-' }}</div>
        </div>
        <div>
            <div class="muted" style="font-size:12px">To Warehouse</div>
            <div style="font-weight:750">{{ $warehouseTransfer->toWarehouse?->name ?? '-' }}</div>
        </div>
    </div>

    <h3>Items to Move</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th>Qty</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warehouseTransfer->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                    <td>{{ $item->inventoryBatch?->id ? 'Batch #' . $item->inventoryBatch->id : 'Auto (FIFO)' }}</td>
                    <td>{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td>{{ $item->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <form method="post" action="{{ route('warehouse-transfers.finalize', $warehouseTransfer) }}" style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
        @csrf
        <a href="{{ route('warehouse-transfers.show', $warehouseTransfer) }}" class="btn secondary">Cancel</a>
        <button class="btn" style="background:#16a34a;color:#fff">
            <i class="fa-solid fa-check"></i>
            Confirm Finalize
        </button>
    </form>
</section>
@endsection
