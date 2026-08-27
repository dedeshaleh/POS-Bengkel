@extends('layouts.app')

@section('title', 'Laporan Stok')
@section('subtitle', 'Snapshot stok terkini dan nilai persediaan berdasarkan harga beli (FIFO).')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
@include('reports._filter', ['showDates' => false])

<div class="grid rpt-cards" style="grid-template-columns:repeat(4,minmax(0,1fr))">
    <div class="stat card">
        <span class="muted">Total SKU</span>
        <strong>{{ number_format($summary['skus'], 0, ',', '.') }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Total Unit Stok</span>
        <strong>{{ number_format($summary['units'], 0, ',', '.') }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Nilai Persediaan</span>
        <strong>{{ $rp($summary['value']) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Stok Menipis / Habis</span>
        <strong class="rpt-neg">{{ $summary['low'] }} / {{ $summary['out'] }}</strong>
    </div>
</div>

<div class="grid two" style="margin-top:18px">
    <section class="panel">
        <h2>Top Nilai Persediaan</h2>
        @if ($topValue->isEmpty())
            <div class="rpt-empty">Belum ada data stok.</div>
        @else
            <div style="position:relative;height:300px"><canvas id="stockChart"></canvas></div>
        @endif
    </section>
    <section class="panel">
        <h2>Nilai per Kategori</h2>
        @if ($byCategory->isEmpty())
            <div class="rpt-empty">Belum ada data.</div>
        @else
            <div style="position:relative;height:300px"><canvas id="stockCatChart"></canvas></div>
        @endif
    </section>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Daftar Stok Produk</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th class="rpt-num">Stok</th>
                <th class="rpt-num">Min</th>
                <th class="rpt-num">Nilai (HPP)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                @php
                    if ($r->total_stock <= 0) { $cls = 'danger'; $txt = 'Habis'; }
                    elseif ($r->total_stock <= $r->min_stock_level) { $cls = ''; $txt = 'Menipis'; }
                    else { $cls = 'success'; $txt = 'Aman'; }
                @endphp
                <tr>
                    <td><strong>{{ $r->name }}</strong><div class="muted" style="font-size:12px">{{ $r->sku }}</div></td>
                    <td>{{ $r->category }}</td>
                    <td class="rpt-num">{{ number_format($r->total_stock, 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ number_format($r->min_stock_level, 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($r->stock_value) }}</td>
                    <td><span class="badge {{ $cls }}">{{ $txt }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr style="font-weight:800">
                    <td colspan="2">TOTAL</td>
                    <td class="rpt-num">{{ number_format($summary['units'], 0, ',', '.') }}</td>
                    <td></td>
                    <td class="rpt-num">{{ $rp($summary['value']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@if ($rows->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const topLabels = @json($topValue->map(fn ($r) => $r->name));
        const topData = @json($topValue->map(fn ($r) => (float) $r->stock_value));
        const sc = document.getElementById('stockChart');
        if (sc && topData.length && typeof Chart !== 'undefined') {
            new Chart(sc, {
                type: 'bar',
                data: { labels: topLabels, datasets: [{ label: 'Nilai Stok', data: topData, backgroundColor: 'rgba(168,85,247,.75)', hoverBackgroundColor: 'rgba(147,51,234,1)', borderRadius: 8 }] },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => 'Rp ' + c.parsed.x.toLocaleString('id-ID') } } },
                    scales: { x: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }, grid: { color: '#eef2f7' } }, y: { grid: { display: false } } },
                },
            });
        }

        const catLabels = @json($byCategory->pluck('category'));
        const catData = @json($byCategory->pluck('value'));
        const scc = document.getElementById('stockCatChart');
        if (scc && catData.length && typeof Chart !== 'undefined') {
            new Chart(scc, {
                type: 'doughnut',
                data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: ['#f97316','#3b82f6','#16a34a','#a855f7','#ef4444','#14b8a6','#eab308','#64748b'], borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: (c) => c.label + ': Rp ' + c.parsed.toLocaleString('id-ID') } } } },
            });
        }
    })();
</script>
@endif
@endsection
