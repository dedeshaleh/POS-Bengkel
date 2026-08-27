@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')
@section('subtitle', 'Omzet dikurangi harga pokok penjualan (HPP/FIFO) dari penjualan selesai.')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
@include('reports._filter')

<div class="grid rpt-cards">
    <div class="stat card">
        <span class="muted">Omzet (Net)</span>
        <strong>{{ $rp($revenue) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">HPP (COGS)</span>
        <strong>{{ $rp($cogs) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Laba Kotor</span>
        <strong class="{{ $grossProfit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ $rp($grossProfit) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Margin</span>
        <strong class="{{ $grossProfit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ number_format($margin, 1, ',', '.') }}%</strong>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));margin-top:18px">
    <div class="stat card">
        <span class="muted">Barang Terjual</span>
        <strong>{{ number_format($unitsSold, 0, ',', '.') }} unit</strong>
    </div>
    <div class="stat card">
        <span class="muted">Total Keuntungan</span>
        <strong class="rpt-pos">{{ $rp($totalProfit) }}</strong>
        <div class="muted" style="font-size:12px;margin-top:6px">Dari produk yang untung</div>
    </div>
    <div class="stat card">
        <span class="muted">Total Kerugian</span>
        <strong class="rpt-neg">{{ $rp($totalLoss) }}</strong>
        <div class="muted" style="font-size:12px;margin-top:6px">Dari produk yang rugi</div>
    </div>
</div>

<div class="notice" style="margin-top:18px">
    <strong>Catatan:</strong> Omzet bersih tidak termasuk PPN ({{ $rp($tax) }} pada rentang ini). HPP dihitung dari harga beli historis batch (FIFO) yang tercatat pada setiap item penjualan.
</div>

<div class="panel">
    <h2>Tren Laba Harian</h2>
    @if ($daily->isEmpty())
        <div class="rpt-empty"><i class="fa-regular fa-chart-bar"></i> Belum ada penjualan pada rentang ini.</div>
    @else
        <div style="position:relative;height:300px"><canvas id="plChart"></canvas></div>
    @endif
</div>

<div class="panel" style="margin-top:18px">
    <h2>Laba per Produk</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="rpt-num">Qty</th>
                <th class="rpt-num">Omzet</th>
                <th class="rpt-num">HPP</th>
                <th class="rpt-num">Laba</th>
                <th class="rpt-num">Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byProduct as $p)
                @php
                    $profit = (float) $p->revenue - (float) $p->cogs;
                    $m = (float) $p->revenue > 0 ? ($profit / (float) $p->revenue) * 100 : 0;
                @endphp
                <tr>
                    <td><strong>{{ $p->name }}</strong><div class="muted" style="font-size:12px">{{ $p->sku }}</div></td>
                    <td class="rpt-num">{{ number_format($p->qty, 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($p->revenue) }}</td>
                    <td class="rpt-num">{{ $rp($p->cogs) }}</td>
                    <td class="rpt-num {{ $profit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ $rp($profit) }}</td>
                    <td class="rpt-num {{ $profit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ number_format($m, 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada penjualan pada rentang ini.</td></tr>
            @endforelse
        </tbody>
        @if ($byProduct->isNotEmpty())
            <tfoot>
                <tr style="font-weight:800">
                    <td>TOTAL</td>
                    <td class="rpt-num">{{ number_format($byProduct->sum('qty'), 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($revenue) }}</td>
                    <td class="rpt-num">{{ $rp($cogs) }}</td>
                    <td class="rpt-num {{ $grossProfit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ $rp($grossProfit) }}</td>
                    <td class="rpt-num {{ $grossProfit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ number_format($margin, 1, ',', '.') }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@if ($daily->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const ctx = document.getElementById('plChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = @json($daily->map(fn ($d) => \Carbon\Carbon::parse($d->d)->format('d/m')));
        const revenue = @json($daily->map(fn ($d) => (float) $d->revenue));
        const profit = @json($daily->map(fn ($d) => (float) $d->revenue - (float) $d->cogs));
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Omzet', data: revenue, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.12)', fill: true, tension: .35, pointRadius: 3 },
                    { label: 'Laba', data: profit, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35, pointRadius: 3 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': Rp ' + c.parsed.y.toLocaleString('id-ID') } },
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
