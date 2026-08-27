@extends('layouts.app')

@section('title', 'Detail Shift Kasir')
@section('subtitle', 'Rekonsiliasi shift ' . $shift->shift_date?->format('Y-m-d'))

@section('content')
<section class="panel" style="max-width:900px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h2>Detail Shift</h2>
        <a class="btn secondary" href="{{ route('cashier-shifts.index') }}">Back</a>
    </div>

    <table class="table">
        <tbody>
            <tr><th style="width:200px">Kasir</th><td>{{ $shift->user?->name }}</td></tr>
            <tr><th>Tanggal</th><td>{{ $shift->shift_date?->format('Y-m-d') }}</td></tr>
            <tr><th>Dibuka</th><td>{{ $shift->opened_at?->format('Y-m-d H:i') }}</td></tr>
            <tr><th>Ditutup</th><td>{{ $shift->closed_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
            <tr><th>Opening Cash</th><td>{{ number_format((float) $shift->opening_cash, 2) }}</td></tr>
            <tr><th>Total Penjualan Tunai</th><td>{{ number_format($shift->totalCashSales(), 2) }}</td></tr>
            <tr><th>Expected Cash</th><td>{{ $shift->expected_closing_cash !== null ? number_format((float) $shift->expected_closing_cash, 2) : '-' }}</td></tr>
            <tr><th>Counted Cash</th><td>{{ $shift->counted_closing_cash !== null ? number_format((float) $shift->counted_closing_cash, 2) : '-' }}</td></tr>
            <tr><th>Selisih</th><td>
                @if ($shift->cash_difference !== null)
                    @if ((float) $shift->cash_difference < 0)
                        <span style="color:#b42318">{{ number_format((float) $shift->cash_difference, 2) }}</span>
                    @elseif ((float) $shift->cash_difference > 0)
                        <span style="color:#16a34a">+{{ number_format((float) $shift->cash_difference, 2) }}</span>
                    @else
                        0.00
                    @endif
                @else
                    -
                @endif
            </td></tr>
            <tr><th>Catatan</th><td>{{ $shift->note ?: '-' }}</td></tr>
        </tbody>
    </table>

    <h3 style="margin:20px 0 8px">Penjualan ({{ $shift->sales->count() }})</h3>
    <table class="table">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Customer</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shift->sales as $sale)
                <tr>
                    <td>{{ $sale->receipt_number }}</td>
                    <td>{{ $sale->customer?->name ?? '-' }}</td>
                    <td>{{ $sale->payment_method ?? '-' }}</td>
                    <td>{{ number_format((float) $sale->grand_total, 2) }}</td>
                    <td>{{ $sale->payment_status }}</td>
                    <td>{{ $sale->sale_date?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Tidak ada penjualan.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
