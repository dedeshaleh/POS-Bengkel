@extends('layouts.app')

@section('title', 'Stock Adjustments')
@section('subtitle', 'Stock opname and quantity adjustments.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Stock Adjustments</h2>
        <a href="{{ route('stock-adjustments.create') }}" class="btn">Add Stock Adjustment</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Warehouse</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Items</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockAdjustments as $stockAdjustment)
                @php
                    $statusColor = match ($stockAdjustment->status) {
                        'finalized' => 'background:#dcfce7;color:#166534',
                        default => 'background:#fef3c7;color:#92400e',
                    };
                @endphp
                <tr>
                    <td>{{ $stockAdjustment->adjustment_date?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $stockAdjustment->warehouse?->name ?? '-' }}</td>
                    <td>{{ $stockAdjustment->reason }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($stockAdjustment->status)->replace('_', ' ')->title() }}</span></td>
                    <td>{{ $stockAdjustment->items_count ?? $stockAdjustment->items->count() }}</td>
                    <td>{{ $stockAdjustment->creator?->name ?? '-' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('stock-adjustments.show', $stockAdjustment) }}">View</a>
                            <a class="btn" href="{{ route('stock-adjustments.edit', $stockAdjustment) }}">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No stock adjustments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('partials.pager', ['paginator' => $stockAdjustments])
@endsection
