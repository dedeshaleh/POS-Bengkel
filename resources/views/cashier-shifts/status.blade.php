@extends('layouts.app')

@section('title', 'Shift Kasir')
@section('subtitle', 'Buka / tutup shift kasir dan kas drawer.')

@section('content')
<section class="panel" style="max-width:700px">
    @if ($shift)
        <h2>Shift Aktif</h2>
        <table class="table">
            <tbody>
                <tr><th style="width:200px">Kasir</th><td>{{ $shift->user?->name }}</td></tr>
                <tr><th>Tanggal</th><td>{{ $shift->shift_date?->format('Y-m-d') }}</td></tr>
                <tr><th>Dibuka</th><td>{{ $shift->opened_at?->format('Y-m-d H:i') }}</td></tr>
                <tr><th>Opening Cash</th><td>{{ number_format((float) $shift->opening_cash, 2) }}</td></tr>
                <tr><th>Total Penjualan Tunai</th><td>{{ number_format($shift->totalCashSales(), 2) }}</td></tr>
                <tr><th>Expected Cash</th><td>{{ number_format($shift->expectedCash(), 2) }}</td></tr>
            </tbody>
        </table>

        @if ($shift->sales->isNotEmpty())
            <h3 style="margin:16px 0 8px">Penjualan Terbaru ({{ $shift->sales->count() }})</h3>
            <table class="table">
                <thead>
                    <tr><th>No. Invoice</th><th>Customer</th><th>Total</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($shift->sales as $sale)
                        <tr>
                            <td>{{ $sale->receipt_number }}</td>
                            <td>{{ $sale->customer?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $sale->grand_total, 2) }}</td>
                            <td>{{ $sale->payment_status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3 style="margin:20px 0 8px">Tutup Shift</h3>
        <form method="post" action="{{ route('cashier-shifts.close') }}">
            @csrf
            <div class="form-grid">
                <label>
                    Hitung Kas Akhir
                    <input type="number" name="counted_closing_cash" step="0.01" min="0" value="{{ old('counted_closing_cash', $shift->expectedCash()) }}" required>
                </label>
                <label class="full">
                    Catatan
                    <textarea name="note" rows="2">{{ old('note') }}</textarea>
                </label>
            </div>
            <button type="submit" class="btn" onclick="return confirm('Tutup shift?')">Tutup Shift</button>
        </form>
    @else
        <h2>Buka Shift Baru</h2>
        <p class="muted">Belum ada shift aktif. Isi saldo awal kas drawer untuk memulai.</p>
        <form method="post" action="{{ route('cashier-shifts.open') }}">
            @csrf
            <div class="form-grid">
                <label>
                    Opening Cash (Saldo Awal Drawer)
                    <input type="number" name="opening_cash" step="0.01" min="0" value="{{ old('opening_cash', 0) }}" required>
                </label>
                <label class="full">
                    Catatan
                    <textarea name="note" rows="2">{{ old('note') }}</textarea>
                </label>
            </div>
            <button type="submit" class="btn">Buka Shift</button>
        </form>
    @endif
</section>
@endsection
