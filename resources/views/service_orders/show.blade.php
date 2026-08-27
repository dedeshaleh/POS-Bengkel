@extends('layouts.app')

@section('title', 'Service Order Detail')
@section('subtitle', $serviceOrder->order_number)

@section('content')
@php
    $statusColor = match ($serviceOrder->status) {
        'completed' => 'background:#dcfce7;color:#166534',
        'in_progress' => 'background:#dbeafe;color:#1d4ed8',
        'cancelled' => 'background:#fee2e2;color:#991b1b',
        default => 'background:#fef3c7;color:#92400e',
    };
@endphp

<section class="panel" style="max-width:980px">
    @if (session('status'))
        <div class="badge" style="background:#dcfce7;color:#166534;margin-bottom:12px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px;white-space:pre-line">{{ session('error') }}</div>
    @endif

    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">{{ $serviceOrder->order_number }}</h2>
            <div class="muted">{{ $serviceOrder->customer?->name ?? 'No customer' }} {{ $serviceOrder->customer?->license_plate ? "({$serviceOrder->customer->license_plate})" : '' }}</div>
            <div class="muted">Mechanic: {{ $serviceOrder->mechanic?->name ?? '-' }}</div>
        </div>
        <span class="badge" style="{{ $statusColor }}">{{ str($serviceOrder->status)->replace('_', ' ')->title() }}</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;margin-bottom:16px">
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Estimated Completion</div>
            <div style="font-weight:750">{{ $serviceOrder->estimated_completion?->format('d M Y') ?? '-' }}</div>
        </div>
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Parts Subtotal</div>
            <div style="font-weight:750">Rp {{ number_format($serviceOrder->parts_subtotal, 0, ',', '.') }}</div>
        </div>
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Labor Cost (Jasa)</div>
            <div style="font-weight:750">Rp {{ number_format($serviceOrder->labor_cost, 0, ',', '.') }}</div>
        </div>
        <div class="panel" style="padding:12px">
            <div class="muted" style="font-size:12px">Other Cost</div>
            <div style="font-weight:750">Rp {{ number_format($serviceOrder->other_cost, 0, ',', '.') }}</div>
        </div>
        <div class="panel" style="padding:12px;background:#fff7ed;border-color:#fed7aa">
            <div class="muted" style="font-size:12px">Total Amount</div>
            <div style="font-weight:800;font-size:18px;color:#c2410c">Rp {{ number_format($serviceOrder->total_amount, 0, ',', '.') }}</div>
        </div>
    </div>

    @if ($serviceOrder->notes)
        <div class="notice" style="margin-bottom:16px">
            <strong>Notes:</strong> {{ $serviceOrder->notes }}
        </div>
    @endif

    <h3>Items</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Buy Price</th>
                <th>Sell Price</th>
                <th>Notes</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($serviceOrder->items as $item)
                @php
                    $typeBadge = match($item->item_type) {
                        'service' => 'background:#dbeafe;color:#1d4ed8',
                        'other' => 'background:#e2e8f0;color:#475569',
                        default => 'background:#dcfce7;color:#166534',
                    };
                    $typeLabel = match($item->item_type) {
                        'service' => 'Jasa',
                        'other' => 'Lainnya',
                        default => 'Sparepart',
                    };
                @endphp
                <tr>
                    <td><span class="badge" style="{{ $typeBadge }}">{{ $typeLabel }}</span></td>
                    <td>
                        @if ($item->product)
                            {{ $item->product->sku }} - {{ $item->product->name }}
                        @else
                            {{ $item->item_name ?? '-' }}
                        @endif
                    </td>
                    <td>{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                    <td>{{ $item->notes ?: '-' }}</td>
                    <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals" style="display:grid;gap:8px;justify-content:end;margin-top:14px">
        <div><strong>Parts:</strong> Rp {{ number_format($serviceOrder->parts_subtotal, 0, ',', '.') }}</div>
        <div><strong>Labor:</strong> Rp {{ number_format($serviceOrder->labor_cost, 0, ',', '.') }}</div>
        <div><strong>Other:</strong> Rp {{ number_format($serviceOrder->other_cost, 0, ',', '.') }}</div>
        <div style="font-size:18px;font-weight:800;color:#c2410c">Total: Rp {{ number_format($serviceOrder->total_amount, 0, ',', '.') }}</div>
    </div>

    @if ($serviceOrder->sale_id)
        <div class="notice" style="margin-top:16px;background:#dbeafe;border-color:#93c5fd">
            <i class="fa-solid fa-link"></i> Linked to Sale: <strong>{{ $serviceOrder->sale?->receipt_number }}</strong>
            — Status: <strong>{{ $serviceOrder->sale?->payment_status ?? 'unknown' }}</strong>
        </div>
    @endif

    <div class="action-bar" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        @if (in_array($serviceOrder->status, ['pending', 'in_progress']) && !$serviceOrder->sale_id)
            <form method="post" action="{{ route('service-orders.complete-and-pay', $serviceOrder) }}" onsubmit="return confirm('Selesaikan service dan lanjut ke pembayaran?')">
                @csrf
                <button type="submit" class="btn" style="background:#16a34a"><i class="fa-solid fa-check-circle"></i> Complete & Pay</button>
            </form>
            <form method="post" action="{{ route('service-orders.send-to-pos', $serviceOrder) }}" onsubmit="return confirm('Kirim ke POS Kasir untuk edit?')">
                @csrf
                <button type="submit" class="btn"><i class="fa-solid fa-cash-register"></i> Send to POS</button>
            </form>
        @endif
        <a class="btn secondary" href="{{ route('service-orders.edit', $serviceOrder) }}">
            <i class="fa-regular fa-pen-to-square"></i> Edit
        </a>
        @if ($serviceOrder->sale_id && $serviceOrder->sale && $serviceOrder->sale->payment_status !== 'paid')
            <a class="btn" href="{{ route('modules.pos.payment', $serviceOrder->sale_id) }}">
                <i class="fa-solid fa-money-bill-wave"></i> Pay
            </a>
        @endif
        <a class="btn" href="{{ route('service-orders.index') }}" style="background:#64748b;color:#fff">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</section>
@endsection
