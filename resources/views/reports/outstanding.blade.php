@extends('layouts.app')

@section('title', 'Laporan Piutang')
@section('subtitle', 'Tagihan pelanggan yang belum lunas (outstanding bill) beserta umur piutang.')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
@include('reports._filter', ['showDates' => false])

<div class="grid rpt-cards" style="grid-template-columns:repeat(4,minmax(0,1fr))">
    <div class="stat card">
        <span class="muted">Total Piutang</span>
        <strong>{{ $rp($summary['total']) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Jumlah Tagihan</span>
        <strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Jatuh Tempo (Overdue)</span>
        <strong class="rpt-neg">{{ $rp($summary['overdue_amount']) }}</strong>
    </div>
    <div class="stat card">
        <span class="muted">Tagihan Overdue</span>
        <strong class="rpt-neg">{{ number_format($summary['overdue_count'], 0, ',', '.') }}</strong>
    </div>
</div>

<div class="grid two" style="margin-top:18px">
    <section class="panel">
        <h2>Umur Piutang (Aging)</h2>
        @if ($summary['count'] === 0)
            <div class="rpt-empty"><i class="fa-regular fa-circle-check"></i> Tidak ada piutang berjalan.</div>
        @else
            <div style="position:relative;height:300px"><canvas id="agingChart"></canvas></div>
        @endif
    </section>
    <section class="panel">
        <h2>Ringkasan Aging</h2>
        <table class="table">
            <thead><tr><th>Kategori</th><th class="rpt-num">Tagihan</th><th class="rpt-num">Nilai</th></tr></thead>
            <tbody>
                @foreach ($buckets as $b)
                    <tr>
                        <td>{{ $b['label'] }}</td>
                        <td class="rpt-num">{{ number_format($b['count'], 0, ',', '.') }}</td>
                        <td class="rpt-num">{{ $rp($b['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:800">
                    <td>TOTAL</td>
                    <td class="rpt-num">{{ number_format($summary['count'], 0, ',', '.') }}</td>
                    <td class="rpt-num">{{ $rp($summary['total']) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>
</div>

<div class="panel" style="margin-top:18px">
    <h2>Daftar Tagihan Belum Lunas</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Pelanggan</th>
                <th>Struk</th>
                <th>Jatuh Tempo</th>
                <th class="rpt-num">Total</th>
                <th class="rpt-num">Dibayar</th>
                <th class="rpt-num">Sisa</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($debts as $d)
                @php
                    $over = $d->days_overdue ?? 0;
                    if ($over > 0) { $cls = 'danger'; $txt = $over . ' hari telat'; }
                    else { $cls = 'muted'; $txt = abs($over) . ' hari lagi'; }
                @endphp
                <tr>
                    <td><strong>{{ $d->customer?->name ?? '-' }}</strong></td>
                    <td>{{ $d->sale?->receipt_number ?? '-' }}</td>
                    <td>{{ optional($d->due_date)->translatedFormat('d M Y') ?? '-' }}</td>
                    <td class="rpt-num">{{ $rp($d->total_debt) }}</td>
                    <td class="rpt-num">{{ $rp($d->amount_paid) }}</td>
                    <td class="rpt-num rpt-neg">{{ $rp($d->remaining_debt) }}</td>
                    <td><span class="badge {{ $cls }}">{{ $txt }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Tidak ada piutang berjalan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($summary['count'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const ctx = document.getElementById('agingChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = @json(collect($buckets)->pluck('label'));
        const data = @json(collect($buckets)->pluck('amount'));
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Nilai Piutang',
                    data,
                    backgroundColor: ['#64748b', '#eab308', '#f97316', '#ef4444'],
                    borderRadius: 8,
                    maxBarThickness: 64,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => 'Rp ' + c.parsed.y.toLocaleString('id-ID') } } },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } },
            },
        });
    })();
</script>
@endif
@endsection
