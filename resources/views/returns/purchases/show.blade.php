@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')
@section('subtitle', $return->return_number)

@section('content')
<section class="panel" style="max-width:900px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h2>{{ $return->return_number }}</h2>
        <div style="display:flex;gap:8px">
            <a class="btn secondary" href="{{ route('returns.purchases.index') }}">Back</a>
            @if ($return->status === 'draft')
                <form method="post" action="{{ route('returns.purchases.approve', $return) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn" onclick="return confirm('Approve retur? Stok akan dikurangi.')">Approve</button>
                </form>
            @endif
        </div>
    </div>

    <table class="table">
        <tbody>
            <tr><th style="width:200px">Supplier</th><td>{{ $return->supplier?->company_name ?? '-' }}</td></tr>
            <tr><th>PO Referensi</th><td>{{ $return->purchase?->invoice_number ?? '-' }}</td></tr>
            <tr><th>Tanggal</th><td>{{ $return->return_date?->format('Y-m-d') }}</td></tr>
            <tr><th>Alasan</th><td>{{ $return->reason ?: '-' }}</td></tr>
            <tr><th>Catatan</th><td>{{ $return->note ?: '-' }}</td></tr>
            <tr><th>Status</th><td>
                @if ($return->status === 'approved')
                    <span class="badge" style="background:#16a34a">Approved</span>
                @else
                    <span class="badge" style="background:#ca8a04">Draft</span>
                @endif
            </td></tr>
            <tr><th>Total</th><td>{{ number_format((float) $return->total_amount, 2) }}</td></tr>
        </tbody>
    </table>

    <h3 style="margin:20px 0 10px">Items</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th>Qty</th>
                <th>Buy Price</th>
                <th>Subtotal</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($return->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                    <td>{{ $item->inventory_batch_id ? 'Batch #' . $item->inventory_batch_id : 'Auto (FIFO)' }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format((float) $item->buy_price, 2) }}</td>
                    <td>{{ number_format((float) $item->subtotal, 2) }}</td>
                    <td>{{ $item->reason ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
