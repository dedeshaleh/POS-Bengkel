@extends('layouts.app')

@section('title', 'Laporan Pajak (PPN)')
@section('subtitle', 'Rekapitulasi PPN keluaran yang dipungut dari penjualan selesai.')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
@include('reports._filter')

<div class="grid rpt-cards">
    <div class="stat card">
        <span class="muted">Total Transaksi</span>
        <strong>{{ number_format($summary->trx ?? 0, 0, ',', '.') }}</strong>
        <div class="muted" style="font-size:12px;margin-top:6px">{{ $taxedTrx }} transaksi kena PPN</div>
    </div>
    <div class="stat card">
        <span class="muted">DPP (Dasar Pengenaan)</span>
        <strong>{{ $rp($summary->dpp ?? 0) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">PPN Keluaran</span>
        <strong>{{ $rp($summary->ppn ?? 0) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Total Termasuk PPN</span>
        <strong>{{ $rp($summary->total ?? 0) }}</strong>
    </div>
</div>

<div class="notice" style="margin-top:18px">
    <strong>PPN Keluaran</strong> adalah pajak yang dipungut dari pelanggan saat penjualan dan menjadi kewajiban setor ke kas negara. DPP dihitung dari subtotal dikurangi diskon penjualan.
</div>

<div class="panel">
    <h2>Tren PPN Harian</h2>
    @if ($daily->isEmpty())
        <div class="rpt-empty"><i class="fa-regular fa-chart-bar"></i> Belum ada penjualan pada rentang ini.</div>
    @else
        <div style="position:relative;height:300px"><canvas id="taxChart"></canvas></div>
    @endif
</div>

<div class="grid two" style="margin-top:18px">
    <section class="panel">
        <h2>Rincian Harian</h2>
        <table class="table">
            <thead><tr><th>Tanggal</th><th class="rpt-num">Trx</th><th class="rpt-num">DPP</th><th class="rpt-num">PPN</th></tr></thead>
            <tbody>
                @forelse ($daily as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d->d)->translatedFormat('d M Y') }}</td>
                        <td class="rpt-num">{{ number_format($d->trx, 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($d->dpp) }}</td>
                        <td class="rpt-num">{{ $rp($d->ppn) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
            @if ($daily->isNotEmpty())
                <tfoot>
                    <tr style="font-weight:800">
                        <td>TOTAL</td>
                        <td class="rpt-num">{{ number_format($summary->trx, 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($summary->dpp) }}</td>
                        <td class="rpt-num">{{ $rp($summary->ppn) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </section>
    <section class="panel">
        <h2>Transaksi Kena PPN</h2>
        <table class="table">
            <thead><tr><th>Struk</th><th>Tanggal</th><th class="rpt-num">PPN</th></tr></thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td>{{ $t->receipt_number }}<div class="muted" style="font-size:12px">{{ $t->customer?->name ?? 'Walk-in' }}</div></td>
                        <td>{{ optional($t->sale_date)->translatedFormat('d M Y') }}</td>
                        <td class="rpt-num">{{ $rp($t->tax_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada transaksi kena PPN.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

@if ($daily->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const ctx = document.getElementById('taxChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = @json($daily->map(fn ($d) => \Carbon\Carbon::parse($d->d)->format('d/m')));
        const ppn = @json($daily->map(fn ($d) => (float) $d->ppn));
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'PPN',
                    data: ppn,
                    backgroundColor: 'rgba(59, 130, 246, .75)',
                    hoverBackgroundColor: 'rgba(37, 99, 235, 1)',
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
