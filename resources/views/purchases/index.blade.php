@extends('layouts.app')

@section('title', 'Purchases')
@section('subtitle', 'Purchase Order, supplier PPN, and Good Receive tracking.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Purchase Transactions</h2>
        <a href="{{ route('purchases.create') }}" class="btn">Add Purchase</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>Date</th>
                <th>Status</th>
                <th>Items</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>PPN</th>
                <th>PPh</th>
                <th>DPP Barang/Jasa</th>
                <th>Grand Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                @php
                    $statusColor = match ($purchase->status) {
                        'closed' => 'background:#dcfce7;color:#166534',
                        'on_order' => 'background:#dbeafe;color:#1d4ed8',
                        default => 'background:#fef3c7;color:#92400e',
                    };
                @endphp
                <tr>
                    <td>{{ $purchase->invoice_number }}</td>
                    <td>{{ $purchase->supplier?->company_name ?? 'No supplier' }}</td>
                    <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($purchase->status)->replace('_', ' ')->title() }}</span></td>
                    <td>{{ $purchase->items_count ?? $purchase->items->count() }}</td>
                    <td>Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($purchase->discount_amount ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($purchase->ppn_percentage, 2, ',', '.') }}% / Rp {{ number_format($purchase->ppn_amount, 0, ',', '.') }}</td>
                    <td>{{ $purchase->withholding_tax_name ?: '-' }} {{ number_format($purchase->withholding_tax_percentage ?? 0, 2, ',', '.') }}% / Rp {{ number_format($purchase->withholding_tax_amount ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($purchase->dpp_goods_amount ?? 0, 0, ',', '.') }} / Rp {{ number_format($purchase->dpp_services_amount ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($purchase->grand_total ?: $purchase->total_amount, 0, ',', '.') }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('purchases.show', $purchase) }}">View</a>
                            @if ($purchase->status === 'on_order')
                                <a class="btn " href="{{ route('purchases.receive.form', $purchase) }}">Good Receive</a>
                            @endif
                            @if ($purchase->status !== 'closed')
                                <form method="post" action="{{ route('purchases.close', $purchase) }}">
                                    @csrf
                                    @method('patch')
                                    <button class="btn" style="background:#b42318">Close PO</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="muted">No purchases yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('partials.pager', ['paginator' => $purchases])
@endsection
