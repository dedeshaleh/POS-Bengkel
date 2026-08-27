@extends('layouts.app')

@section('title', 'Finalize Stock Adjustment')
@section('subtitle', 'Confirm stock adjustment posting.')

@section('content')
<section class="panel" style="max-width:1100px">
    <h2 style="margin-top:0">Finalize Stock Adjustment</h2>

    <div class="notice" style="border-color:#f59e0b;background:#fffbeb;margin-bottom:16px">
        <strong>Warning:</strong> Finalizing will apply the calculated differences to inventory batches (or product total stock) and cannot be undone.
    </div>

    <div style="margin-bottom:16px">
        <div class="muted" style="font-size:12px">Warehouse</div>
        <div style="font-weight:750">{{ $stockAdjustment->warehouse?->name ?? '-' }}</div>
    </div>
    <div style="margin-bottom:16px">
        <div class="muted" style="font-size:12px">Adjustment Date</div>
        <div style="font-weight:750">{{ $stockAdjustment->adjustment_date?->format('d M Y') ?? '-' }}</div>
    </div>
    <div style="margin-bottom:16px">
        <div class="muted" style="font-size:12px">Reason</div>
        <div style="font-weight:750">{{ $stockAdjustment->reason }}</div>
    </div>

    <h3>Items to Apply</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th>Expected Qty</th>
                <th>Actual Qty</th>
                <th>Difference</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockAdjustment->items as $item)
                @php
                    $diffColor = $item->difference > 0
                        ? 'color:#166534'
                        : ($item->difference < 0 ? 'color:#991b1b' : '');
                @endphp
                <tr>
                    <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                    <td>{{ $item->inventoryBatch?->id ? 'Batch #' . $item->inventoryBatch->id : '-' }}</td>
                    <td>{{ number_format($item->expected_qty, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->actual_qty, 0, ',', '.') }}</td>
                    <td style="{{ $diffColor }};font-weight:750">
                        {{ ($item->difference > 0 ? '+' : '') . number_format($item->difference, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <form method="post" action="{{ route('stock-adjustments.finalize', $stockAdjustment) }}" style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
        @csrf
        <a href="{{ route('stock-adjustments.show', $stockAdjustment) }}" class="btn secondary">Cancel</a>
        <button class="btn" style="background:#16a34a;color:#fff">
            <i class="fa-solid fa-check"></i>
            Confirm Finalize
        </button>
    </form>
</section>
@endsection
