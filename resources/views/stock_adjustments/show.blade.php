@extends('layouts.app')

@section('title', 'Stock Adjustment Detail')
@section('subtitle', 'Stock opname adjustment detail.')

@section('content')
@php
    $statusColor = match ($stockAdjustment->status) {
        'finalized' => 'background:#dcfce7;color:#166534',
        default => 'background:#fef3c7;color:#92400e',
    };
@endphp

<section class="panel" style="max-width:1100px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">Stock Adjustment #{{ $stockAdjustment->id }}</h2>
            <div class="muted">{{ $stockAdjustment->warehouse?->name ?? 'No warehouse' }}</div>
            <div class="muted">Date: {{ $stockAdjustment->adjustment_date?->format('d M Y') ?? '-' }}</div>
        </div>
        <span class="badge" style="{{ $statusColor }}">{{ str($stockAdjustment->status)->replace('_', ' ')->title() }}</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:16px">
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Reason</div>
            <div style="font-weight:750">{{ $stockAdjustment->reason }}</div>
        </div>
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Created By</div>
            <div style="font-weight:750">{{ $stockAdjustment->creator?->name ?? '-' }}</div>
        </div>
    </div>

    @if ($stockAdjustment->notes)
        <div class="notice" style="margin-bottom:16px">
            <strong>Notes:</strong> {{ $stockAdjustment->notes }}
        </div>
    @endif

    <h3>Items</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th>Expected Qty</th>
                <th>Actual Qty</th>
                <th>Difference</th>
                <th>Notes</th>
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
                    <td>{{ $item->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="action-bar" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        @if ($stockAdjustment->status === 'draft')
            <a class="btn" href="{{ route('stock-adjustments.finalize.show', $stockAdjustment) }}" style="background:#16a34a;color:#fff">
                <i class="fa-solid fa-check"></i>
                Finalize
            </a>
            <a class="btn" href="{{ route('stock-adjustments.edit', $stockAdjustment) }}">
                <i class="fa-regular fa-pen-to-square"></i>
                Edit
            </a>
        @endif
        <a class="btn" href="{{ route('stock-adjustments.index') }}" style="background:#64748b;color:#fff">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>
    </div>
</section>
@endsection
