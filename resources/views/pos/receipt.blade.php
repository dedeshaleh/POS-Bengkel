@extends('layouts.app')

@section('title', 'Receipt')
@section('subtitle', 'Transaction completed')

@section('content')
<style>
    .receipt-wrapper { display: flex; justify-content: center; padding: 20px 0; }
    .receipt {
        width: 320px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.6;
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
    }
    .receipt-header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 10px; margin-bottom: 10px; }
    .receipt-header h2 { margin: 0; font-size: 16px; font-weight: bold; }
    .receipt-header p { margin: 2px 0; font-size: 11px; }
    .receipt-meta { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px; }
    .receipt-items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .receipt-items th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 4px 0; font-size: 11px; }
    .receipt-items td { padding: 2px 0; font-size: 11px; vertical-align: top; }
    .receipt-items .right { text-align: right; }
    .receipt-divider { border-top: 1px dashed #cbd5e1; margin: 8px 0; }
    .receipt-totals { font-size: 11px; }
    .receipt-totals .row { display: flex; justify-content: space-between; padding: 1px 0; }
    .receipt-totals .grand { font-weight: bold; font-size: 13px; border-top: 2px solid #1e293b; padding-top: 4px; margin-top: 4px; }
    .receipt-footer { text-align: center; margin-top: 12px; font-size: 10px; color: #64748b; }
    .receipt-actions { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
    @media print {
        body * { visibility: hidden; }
        .receipt-wrapper, .receipt-wrapper * { visibility: visible; }
        .receipt-wrapper { position: absolute; left: 0; top: 0; padding: 0; }
        .receipt { border: none; box-shadow: none; width: 100%; max-width: 320px; }
        .receipt-actions { display: none !important; }
        .topbar, .sidebar, .main > *:not(.receipt-wrapper) { display: none !important; }
    }
</style>
<div class="receipt-wrapper">
    <div>
        <div class="receipt" id="receiptPaper">
            <div class="receipt-header">
                <h2>Bengkel Berkah</h2>
                <p>Jl. Contoh No. 123, Kota</p>
                <p>Telp: 0812-3456-7890</p>
            </div>

            <div class="receipt-meta">
                <span>{{ $sale->receipt_number }}</span>
                <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="receipt-meta">
                <span>Kasir: {{ $sale->cashier->name ?? '-' }}</span>
                <span>{{ strtoupper($sale->payment_status) }}</span>
            </div>

            @if ($sale->customer)
            <div class="receipt-meta">
                <span>Plgn: {{ $sale->customer->name }}</span>
                @if ($sale->customer->license_plate)<span>{{ $sale->customer->license_plate }}</span>@endif
            </div>
            @endif

            <table class="receipt-items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="right">Qty</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Deleted' }}<br><small>{{ $item->product->sku ?? '' }}</small></td>
                        <td class="right">{{ $item->qty }}x</td>
                        <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="receipt-divider"></div>

            <div class="receipt-totals">
                <div class="row"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal_amount, 0, ',', '.') }}</span></div>
                @if ($sale->discount_amount > 0)
                <div class="row"><span>Diskon</span><span>- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span></div>
                @endif
                @if ($sale->tax_amount > 0)
                <div class="row"><span>Pajak ({{ $sale->tax_percentage }}%)</span><span>Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</span></div>
                @endif
                <div class="row grand"><span>TOTAL</span><span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-totals">
                <div class="row"><span>Metode</span><span>{{ strtoupper($sale->payment_method ?? 'cash') }}</span></div>
                <div class="row"><span>Status</span><span>{{ strtoupper($sale->payment_status) }}</span></div>
            </div>

            <div class="receipt-footer">
                <p>Terima kasih atas kunjungan Anda!</p>
                <p>Barang yang sudah dibeli tidak dapat ditukar</p>
            </div>
        </div>

        <div class="receipt-actions">
            <button type="button" class="btn" onclick="window.print()">Print Struk</button>
            <a href="{{ route('modules.pos.open-cashier') }}" class="btn secondary">Close & New Sale</a>
        </div>
    </div>
</div>
@endsection
