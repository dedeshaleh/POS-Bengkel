@extends('layouts.app')

@section('title', 'Purchase Detail')
@section('subtitle', $purchase->invoice_number)

@section('content')
@php
    $statusColor = match ($purchase->status) {
        'closed' => 'background:#dcfce7;color:#166534',
        'on_order' => 'background:#dbeafe;color:#1d4ed8',
        default => 'background:#fef3c7;color:#92400e',
    };
@endphp

<section class="panel" style="max-width:980px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">{{ $purchase->invoice_number }}</h2>
            <div class="muted">{{ $purchase->supplier?->company_name ?? 'No supplier' }} | {{ $purchase->purchase_date->format('d M Y') }}</div>
            <div class="muted">
                {{ $purchase->supplier?->is_ppn_enabled ? 'PKP' : 'Non-PKP' }}
                | {{ str($purchase->supplier?->entity_type ?? 'corporate')->title() }}
                | {{ $purchase->is_government_tax_collector ? 'PPh 22 Collector' : 'Private Buyer' }}
            </div>
        </div>
        <span class="badge" style="{{ $statusColor }}">{{ str($purchase->status)->replace('_', ' ')->title() }}</span>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>UOM</th>
                <th>Ordered</th>
                <th>Received</th>
                <th>Buy Price</th>
                <th>Item Discount</th>
                <th>Receive Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                    <td>{{ $item->purchased_uom_code }}</td>
                    <td>{{ number_format($item->purchased_qty, 2, ',', '.') }}</td>
                    <td>{{ number_format($item->received_qty, 2, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->buy_price_per_purchased_uom, 0, ',', '.') }}</td>
                    <td>
                        {{ str($item->discount_type ?? 'none')->title() }}
                        @if (($item->discount_type ?? 'none') === 'percentage')
                            {{ number_format($item->discount_value ?? 0, 2, ',', '.') }}%
                        @elseif (($item->discount_type ?? 'none') === 'fixed')
                            Rp {{ number_format($item->discount_value ?? 0, 0, ',', '.') }}
                        @endif
                        <div class="muted">Rp {{ number_format($item->discount_amount ?? 0, 0, ',', '.') }}</div>
                    </td>
                    <td>{{ $item->received_price_per_purchased_uom ? 'Rp ' . number_format($item->received_price_per_purchased_uom, 0, ',', '.') : '-' }}</td>
                    <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals" style="display:grid;gap:8px;justify-content:end;margin-top:14px">
        <div><strong>Subtotal:</strong> Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</div>
        <div><strong>Discount:</strong> Rp {{ number_format($purchase->discount_amount ?? 0, 0, ',', '.') }}</div>
        <div><strong>DPP Barang:</strong> Rp {{ number_format($purchase->dpp_goods_amount ?? 0, 0, ',', '.') }}</div>
        <div><strong>DPP Jasa:</strong> Rp {{ number_format($purchase->dpp_services_amount ?? 0, 0, ',', '.') }}</div>
        <div><strong>PPN:</strong> {{ number_format($purchase->ppn_percentage, 2, ',', '.') }}% / Rp {{ number_format($purchase->ppn_amount, 0, ',', '.') }}</div>
        <div><strong>PPh / Tax Potong:</strong> {{ $purchase->withholding_tax_name ?: '-' }} {{ number_format($purchase->withholding_tax_percentage ?? 0, 2, ',', '.') }}% / Rp {{ number_format($purchase->withholding_tax_amount ?? 0, 0, ',', '.') }}</div>
        <div><strong>Grand Total:</strong> Rp {{ number_format($purchase->grand_total ?: $purchase->total_amount, 0, ',', '.') }}</div>
    </div>

    <div class="action-bar" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        @if ($purchase->status === 'draft')
            <a class="btn" href="{{ route('purchases.edit', $purchase) }}">
                <i class="fa-regular fa-pen-to-square"></i>
                Edit
            </a>
            <form method="post" action="{{ route('purchases.activate', $purchase) }}" style="display:inline">
                @csrf
                @method('patch')
                <button class="btn">
                    <i class="fa-solid fa-play"></i>
                    Aktifkan
                </button>
            </form>
        @endif
        @if ($purchase->status === 'on_order')
            <a class="btn" href="{{ route('purchases.receive.form', $purchase) }}">
                <i class="fa-solid fa-download"></i>
                Terima
            </a>
            <a class="btn" target="_blank" href="{{ route('purchases.print', $purchase) }}">
                <i class="fa-solid fa-print"></i>
                Print
            </a>
        @endif
        @if ($purchase->status !== 'closed')
            <form method="post" action="{{ route('purchases.close', $purchase) }}">
                @csrf
                @method('patch')
                <button class="btn" style="background:#b42318">
                    <i class="fa-solid fa-xmark"></i>
                    Tutup
                </button>
            </form>
        @endif
        <a class="btn" href="{{ route('purchases.index') }}" style="background:#64748b;color:#fff">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>
</section>

<section class="panel" style="max-width:980px;margin-top:16px">
    <h2 style="margin-top:0">Good Receive History</h2>
    <table class="table">
        <thead>
            <tr>
                <th>GR Number</th>
                <th>Surat Jalan</th>
                <th>Date</th>
                <th>Warehouse</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Expired</th>
                <th>Base Qty</th>
                <th>Note</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @php $hasGoodReceiveItems = false; @endphp
            @foreach ($purchase->goodReceives as $goodReceive)
                @foreach ($goodReceive->items as $grItem)
                    @php $hasGoodReceiveItems = true; @endphp
                    <tr>
                        <td>{{ $goodReceive->gr_number }}</td>
                        <td>{{ $goodReceive->delivery_note_number }}</td>
                        <td>{{ $goodReceive->received_date->format('d M Y') }}</td>
                        <td>{{ $goodReceive->warehouse?->name ?? '-' }}</td>
                        <td>{{ $grItem->product?->sku }} - {{ $grItem->product?->name }}</td>
                        <td>{{ number_format($grItem->received_qty, 0, ',', '.') }}</td>
                        <td>{{ $grItem->expired_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ number_format($grItem->received_qty_in_base_uom, 0, ',', '.') }}</td>
                        <td>{{ $goodReceive->note ?: '-' }}</td>
                        <td><a class="btn secondary" target="_blank" href="{{ route('purchases.good-receives.print', [$purchase, $goodReceive]) }}">Print</a></td>
                    </tr>
                @endforeach
            @endforeach
            @unless ($hasGoodReceiveItems)
                <tr><td colspan="10" class="muted">Belum ada histori Good Receive.</td></tr>
            @endunless
        </tbody>
    </table>
</section>
@endsection
