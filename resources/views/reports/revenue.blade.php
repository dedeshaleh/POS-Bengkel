@extends('layouts.app')

@section('title', 'Laporan Revenue')
@section('subtitle', 'Ringkasan pendapatan, laba, dan tren bulanan dari penjualan selesai.')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
@endphp

@section('content')
@include('reports._filter')

<div class="grid rpt-cards">
    <div class="stat card">
        <span class="muted">Total Revenue</span>
        <strong>{{ $rp($revenue) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Laba Kotor</span>
        <strong class="{{ $profit >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ $rp($profit) }}</strong>
        <div class="muted" style="font-size:12px;margin-top:6px">Margin {{ number_format($margin, 1, ',', '.') }}%</div>
    </div>
    <div class="stat card">
        <span class="muted">Barang Terjual</span>
        <strong>{{ number_format($unitsSold, 0, ',', '.') }} unit</strong>
        <div class="muted" style="font-size:12px;margin-top:6px">{{ number_format($trx, 0, ',', '.') }} transaksi</div>
    </div>
    <div class="stat card">
        <span class="muted">Piutang Berjalan</span>
        <strong>{{ $rp($outstanding) }}</strong>
        <div class="muted" style="font-size:12px;margin-top:6px"><a href="{{ route('modules.reporting.outstanding') }}">Lihat detail &rarr;</a></div>
    </div>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Tren Revenue &amp; Laba Bulanan</h2>
    @if ($monthly->isEmpty())
        <div class="rpt-empty"><i class="fa-regular fa-chart-bar"></i> Belum ada penjualan pada rentang ini.</div>
    @else
        <div style="position:relative;height:320px"><canvas id="revChart"></canvas></div>
    @endif
</div>

<div class="grid two" style="margin-top:18px">
    <section class="panel">
        <h2>Revenue per Kategori</h2>
        @if ($byCategory->isEmpty())
            <div class="rpt-empty">Belum ada data.</div>
        @else
            <div style="position:relative;height:280px"><canvas id="catChart"></canvas></div>
        @endif
    </section>
    <section class="panel">
        <h2>Rincian Kategori</h2>
        <table class="table">
            <thead><tr><th>Kategori</th><th class="rpt-num">Revenue</th><th class="rpt-num">%</th></tr></thead>
            <tbody>
                @forelse ($byCategory as $c)
                    <tr>
                        <td>{{ $c->category }}</td>
                        <td class="rpt-num">{{ $rp($c->revenue) }}</td>
                        <td class="rpt-num">{{ $revenue > 0 ? number_format($c->revenue / $revenue * 100, 1, ',', '.') : '0,0' }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Rincian Bulanan</h2>
    <table class="table">
        <thead><tr><th>Bulan</th><th class="rpt-num">Revenue</th><th class="rpt-num">HPP</th><th class="rpt-num">Laba</th><th class="rpt-num">Margin</th></tr></thead>
        <tbody>
            @forelse ($monthly as $m)
                @php $p = (float) $m->revenue - (float) $m->cogs; $mg = (float) $m->revenue > 0 ? $p / (float) $m->revenue * 100 : 0; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->ym . '-01')->translatedFormat('M Y') }}</td>
                    <td class="rpt-num">{{ $rp($m->revenue) }}</td>
                    <td class="rpt-num">{{ $rp($m->cogs) }}</td>
                    <td class="rpt-num {{ $p >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ $rp($p) }}</td>
                    <td class="rpt-num {{ $p >= 0 ? 'rpt-pos' : 'rpt-neg' }}">{{ number_format($mg, 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada penjualan pada rentang ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($monthly->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const labels = @json($monthly->map(fn ($m) => \Carbon\Carbon::parse($m->ym . '-01')->format('M y')));
        const revenue = @json($monthly->map(fn ($m) => (float) $m->revenue));
        const profit = @json($monthly->map(fn ($m) => (float) $m->revenue - (float) $m->cogs));
        const rev = document.getElementById('revChart');
        if (rev && typeof Chart !== 'undefined') {
            new Chart(rev, {
                data: {
                    labels,
                    datasets: [
                        { type: 'bar', label: 'Revenue', data: revenue, backgroundColor: 'rgba(249,115,22,.75)', borderRadius: 8, maxBarThickness: 40, order: 2 },
                        { type: 'line', label: 'Laba', data: profit, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.15)', fill: true, tension: .35, pointRadius: 3, order: 1 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': Rp ' + c.parsed.y.toLocaleString('id-ID') } } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } },
                },
            });
        }

        const catLabels = @json($byCategory->map(fn ($c) => $c->category));
        const catData = @json($byCategory->map(fn ($c) => (float) $c->revenue));
        const cat = document.getElementById('catChart');
        if (cat && catData.length && typeof Chart !== 'undefined') {
            new Chart(cat, {
                type: 'doughnut',
                data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: ['#f97316','#3b82f6','#16a34a','#a855f7','#ef4444','#14b8a6','#eab308','#64748b'], borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: (c) => c.label + ': Rp ' + c.parsed.toLocaleString('id-ID') } } } },
            });
        }
    })();
</script>
@endif
@endsection
