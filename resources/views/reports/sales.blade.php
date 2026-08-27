@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('subtitle', 'Rekapitulasi transaksi penjualan yang sudah selesai.')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $avg = ($summary->trx ?? 0) > 0 ? ($summary->total / $summary->trx) : 0;
@endphp

@section('content')
@include('reports._filter')

<div class="grid rpt-cards">
    <div class="stat card">
        <span class="muted">Total Transaksi</span>
        <strong>{{ number_format($summary->trx ?? 0, 0, ',', '.') }}</strong>
        @if ($openHeld > 0)
            <div class="muted" style="font-size:12px;margin-top:6px">+ {{ $openHeld }} servis berjalan (belum selesai)</div>
        @endif
    </div>
    <div class="stat card">
        <span class="muted">Omzet (Grand Total)</span>
        <strong>{{ $rp($summary->total ?? 0) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Total Diskon</span>
        <strong>{{ $rp($summary->discount ?? 0) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Rata-rata / Transaksi</span>
        <strong>{{ $rp($avg) }}</strong>
    </div>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Tren Penjualan Harian</h2>
    @if ($daily->isEmpty())
        <div class="rpt-empty"><i class="fa-regular fa-chart-bar"></i> Belum ada penjualan pada rentang ini.</div>
    @else
        <div style="position:relative;height:300px"><canvas id="salesChart"></canvas></div>
    @endif
</div>

<div class="grid two" style="margin-top:18px">
    <section class="panel">
        <h2>Produk Terlaris</h2>
        <table class="table">
            <thead><tr><th>Produk</th><th class="rpt-num">Qty</th><th class="rpt-num">Omzet</th></tr></thead>
            <tbody>
                @forelse ($topProducts as $p)
                    <tr>
                        <td><strong>{{ $p->name }}</strong><div class="muted" style="font-size:12px">{{ $p->sku }}</div></td>
                        <td class="rpt-num">{{ number_format($p->qty, 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($p->revenue) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <section class="panel">
        <h2>Metode Pembayaran</h2>
        <table class="table">
            <thead><tr><th>Metode</th><th class="rpt-num">Trx</th><th class="rpt-num">Total</th></tr></thead>
            <tbody>
                @forelse ($byMethod as $m)
                    <tr>
                        <td><span class="badge muted">{{ ucfirst($m->method) }}</span></td>
                        <td class="rpt-num">{{ number_format($m->trx, 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($m->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 style="margin-top:20px">Per Kasir</h2>
        <table class="table">
            <thead><tr><th>Kasir</th><th class="rpt-num">Trx</th><th class="rpt-num">Total</th></tr></thead>
            <tbody>
                @forelse ($byCashier as $c)
                    <tr>
                        <td>{{ $c->cashier }}</td>
                        <td class="rpt-num">{{ number_format($c->trx, 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($c->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Rincian Harian</h2>
    <table class="table">
        <thead>
            <tr><th>Tanggal</th><th class="rpt-num">Trx</th><th class="rpt-num">Diskon</th><th class="rpt-num">PPN</th><th class="rpt-num">Grand Total</th></tr>
        </thead>
        <tbody>
            @forelse ($daily as $d)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($d->d)->translatedFormat('d M Y') }}</td>
                    <td class="rpt-num">{{ number_format($d->trx, 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($d->discount) }}</td>
                    <td class="rpt-num">{{ $rp($d->tax) }}</td>
                    <td class="rpt-num">{{ $rp($d->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada penjualan pada rentang ini.</td></tr>
            @endforelse
        </tbody>
        @if ($daily->isNotEmpty())
            <tfoot>
                <tr style="font-weight:800">
                    <td>TOTAL</td>
                    <td class="rpt-num">{{ number_format($summary->trx, 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($summary->discount) }}</td>
                    <td class="rpt-num">{{ $rp($summary->tax) }}</td>
                    <td class="rpt-num">{{ $rp($summary->total) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Transaksi Terbaru</h2>
    <table class="table">
        <thead><tr><th>Struk</th><th>Tanggal</th><th>Pelanggan</th><th>Status Bayar</th><th class="rpt-num">Total</th></tr></thead>
        <tbody>
            @forelse ($recent as $s)
                <tr>
                    <td>{{ $s->receipt_number }}</td>
                    <td>{{ optional($s->sale_date)->translatedFormat('d M Y H:i') }}</td>
                    <td>{{ $s->customer?->name ?? 'Walk-in' }}</td>
                    <td>
                        @php $cls = $s->payment_status === 'paid' ? 'success' : ($s->payment_status === 'partial' ? '' : 'danger'); @endphp
                        <span class="badge {{ $cls }}">{{ ucfirst($s->payment_status) }}</span>
                    </td>
                    <td class="rpt-num">{{ $rp($s->grand_total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($daily->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const ctx = document.getElementById('salesChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = @json($daily->map(fn ($d) => \Carbon\Carbon::parse($d->d)->format('d/m')));
        const totals = @json($daily->map(fn ($d) => (float) $d->total));
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Omzet',
                    data: totals,
                    backgroundColor: 'rgba(249, 115, 22, .75)',
                    hoverBackgroundColor: 'rgba(234, 88, 12, 1)',
                    borderRadius: 8,
                    maxBarThickness: 46,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => 'Rp ' + c.parsed.y.toLocaleString('id-ID') } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }, grid: { color: '#eef2f7' } },
                    x: { grid: { display: false } },
                },
            },
        });
    })();
</script>
@endif
@endsection
