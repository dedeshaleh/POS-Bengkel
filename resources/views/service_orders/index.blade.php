@extends('layouts.app')

@section('title', 'Service Orders')
@section('subtitle', 'Workshop service tracking — active & completed.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Service Orders</h2>
        <a href="{{ route('service-orders.create') }}" class="btn"><i class="fa-solid fa-plus"></i> New Service Order</a>
    </div>

    @if (session('status'))
        <div class="badge" style="background:#dcfce7;color:#166534;margin-bottom:12px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px;white-space:pre-line">{{ session('error') }}</div>
    @endif

    <h3 style="margin:16px 0 8px"><i class="fa-solid fa-screwdriver-wrench"></i> Active Services</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Plate</th>
                <th>Mechanic</th>
                <th>Status</th>
                <th>Est. Done</th>
                <th>Items</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($activeOrders as $so)
                @php
                    $statusColor = match ($so->status) {
                        'in_progress' => 'background:#dbeafe;color:#1d4ed8',
                        default => 'background:#fef3c7;color:#92400e',
                    };
                @endphp
                <tr>
                    <td><strong>{{ $so->order_number }}</strong></td>
                    <td>{{ $so->customer?->name ?? '-' }}</td>
                    <td>{{ $so->customer?->license_plate ?? '-' }}</td>
                    <td>{{ $so->mechanic?->name ?? '-' }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($so->status)->replace('_', ' ')->title() }}</span></td>
                    <td>{{ $so->estimated_completion?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $so->items_count }}</td>
                    <td>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('service-orders.show', $so) }}">View</a>
                            <a class="btn" href="{{ route('service-orders.edit', $so) }}">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No active services.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="panel" style="margin-top:20px">
    <h3 style="margin:0 0 8px"><i class="fa-solid fa-check-circle"></i> Completed / History</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Items</th>
                <th>Total</th>
                <th>Paid?</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($completedOrders as $so)
                @php
                    $statusColor = match ($so->status) {
                        'completed' => 'background:#dcfce7;color:#166534',
                        'cancelled' => 'background:#fee2e2;color:#991b1b',
                        default => 'background:#e2e8f0;color:#475569',
                    };
                @endphp
                <tr>
                    <td><strong>{{ $so->order_number }}</strong></td>
                    <td>{{ $so->customer?->name ?? '-' }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($so->status)->replace('_', ' ')->title() }}</span></td>
                    <td>{{ $so->items_count }}</td>
                    <td>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
                    <td>
                        @if ($so->sale_id)
                            @if ($so->sale && $so->sale->payment_status === 'paid')
                                <span class="badge" style="background:#dcfce7;color:#166534">Paid</span>
                            @else
                                <span class="badge" style="background:#fef3c7;color:#92400e">Unpaid</span>
                            @endif
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('service-orders.show', $so) }}">View</a>
                            @if ($so->sale_id && $so->sale && $so->sale->payment_status !== 'paid')
                                <a class="btn" href="{{ route('modules.pos.payment', $so->sale_id) }}">Pay</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No completed services.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('partials.pager', ['paginator' => $completedOrders])
@endsection
