@extends('layouts.app')

@section('title', 'Warehouse Transfer Detail')
@section('subtitle', 'Warehouse transfer detail.')

@section('content')
@php
    $statusColor = match ($warehouseTransfer->status) {
        'completed' => 'background:#dcfce7;color:#166534',
        default => 'background:#fef3c7;color:#92400e',
    };
@endphp

<section class="panel" style="max-width:1100px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">Transfer {{ $warehouseTransfer->transfer_number }}</h2>
            <div class="muted">From: {{ $warehouseTransfer->fromWarehouse?->name ?? '-' }}</div>
            <div class="muted">To: {{ $warehouseTransfer->toWarehouse?->name ?? '-' }}</div>
            <div class="muted">Date: {{ $warehouseTransfer->transfer_date?->format('d M Y') ?? '-' }}</div>
        </div>
        <span class="badge" style="{{ $statusColor }}">{{ str($warehouseTransfer->status)->replace('_', ' ')->title() }}</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:16px">
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Created By</div>
            <div style="font-weight:750">{{ $warehouseTransfer->creator?->name ?? '-' }}</div>
        </div>
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Total Items</div>
            <div style="font-weight:750">{{ $warehouseTransfer->items->sum('qty') }}</div>
        </div>
    </div>

    @if ($warehouseTransfer->notes)
        <div class="notice" style="margin-bottom:16px">
            <strong>Notes:</strong> {{ $warehouseTransfer->notes }}
        </div>
    @endif

    <h3>Items</h3>
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

    <div class="action-bar" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        @if ($warehouseTransfer->status === 'draft')
            <a class="btn" href="{{ route('warehouse-transfers.finalize.show', $warehouseTransfer) }}" style="background:#16a34a;color:#fff">
                <i class="fa-solid fa-check"></i>
                Finalize
            </a>
            <a class="btn" href="{{ route('warehouse-transfers.edit', $warehouseTransfer) }}">
                <i class="fa-regular fa-pen-to-square"></i>
                Edit
            </a>
        @endif
        <a class="btn" href="{{ route('warehouse-transfers.index') }}" style="background:#64748b;color:#fff">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>
    </div>
</section>
@endsection
