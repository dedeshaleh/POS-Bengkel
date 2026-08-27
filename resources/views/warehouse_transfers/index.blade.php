@extends('layouts.app')

@section('title', 'Warehouse Transfers')
@section('subtitle', 'Move stock between warehouses.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Warehouse Transfers</h2>
        <a href="{{ route('warehouse-transfers.create') }}" class="btn">Add Warehouse Transfer</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Transfer Number</th>
                <th>Date</th>
                <th>From</th>
                <th>To</th>
                <th>Status</th>
                <th>Items</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transfers as $transfer)
                @php
                    $statusColor = match ($transfer->status) {
                        'completed' => 'background:#dcfce7;color:#166534',
                        default => 'background:#fef3c7;color:#92400e',
                    };
                @endphp
                <tr>
                    <td>{{ $transfer->transfer_number }}</td>
                    <td>{{ $transfer->transfer_date?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $transfer->fromWarehouse?->name ?? '-' }}</td>
                    <td>{{ $transfer->toWarehouse?->name ?? '-' }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($transfer->status)->replace('_', ' ')->title() }}</span></td>
                    <td>{{ $transfer->items_count ?? $transfer->items->count() }}</td>
                    <td>{{ $transfer->creator?->name ?? '-' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('warehouse-transfers.show', $transfer) }}">View</a>
                            <a class="btn" href="{{ route('warehouse-transfers.edit', $transfer) }}">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No warehouse transfers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('partials.pager', ['paginator' => $transfers])
@endsection
