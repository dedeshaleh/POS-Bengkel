@extends('layouts.app')

@section('title', 'Workshop Overview')
@section('subtitle', 'Sales, receivables, and stock alerts from your PostgreSQL schema.')

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $alertCount = $outOfStock->count() + $lowStock->count() + $overdueDebts->count();
@endphp

<div class="grid stats">
    <div class="stat card"><span class="muted">Sales today</span><strong>Rp {{ number_format($salesToday, 0, ',', '.') }}</strong></div>
    <div class="stat card"><span class="muted">Open debt</span><strong>Rp {{ number_format($openDebts, 0, ',', '.') }}</strong></div>
    <div class="stat card"><span class="muted">Low stock SKUs</span><strong id="dashLowStockCount">{{ $lowStock->count() + $outOfStock->count() }}</strong></div>
</div>

<div class="grid three" style="margin-top:16px">
    <section class="panel alert-panel" id="alertOutOfStock">
        <div class="alert-header">
            <div class="alert-icon danger"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div>
                <h2>Stok Habis</h2>
                <div class="alert-count" id="outOfStockCount">{{ $outOfStock->count() }} produk</div>
            </div>
        </div>
        <table class="table">
            <thead><tr><th>SKU</th><th>Item</th></tr></thead>
            <tbody id="outOfStockList">
                @forelse ($outOfStock as $product)
                    <tr><td>{{ $product->sku }}</td><td>{{ $product->name }}</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">Semua stok masih tersedia.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($outOfStock->count() > 0)
            <div class="alert-action"><a href="{{ route('modules.reporting.stock') }}" class="btn ghost">Lihat Stok &rarr;</a></div>
        @endif
    </section>

    <section class="panel alert-panel" id="alertLowStock">
        <div class="alert-header">
            <div class="alert-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <h2>Stok Menipis</h2>
                <div class="alert-count" id="lowStockCount">{{ $lowStock->count() }} produk</div>
            </div>
        </div>
        <table class="table">
            <thead><tr><th>SKU</th><th>Item</th><th>Stok</th></tr></thead>
            <tbody id="lowStockList">
                @forelse ($lowStock as $product)
                    <tr><td>{{ $product->sku }}</td><td>{{ $product->name }}</td><td class="danger">{{ $product->total_stock }}</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Stok aman.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($lowStock->count() > 0)
            <div class="alert-action"><a href="{{ route('modules.reporting.stock') }}" class="btn ghost">Lihat Stok &rarr;</a></div>
        @endif
    </section>

    <section class="panel alert-panel" id="alertOverdue">
        <div class="alert-header">
            <div class="alert-icon danger"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div>
                <h2>Piutang Jatuh Tempo</h2>
                <div class="alert-count" id="overdueCount">{{ $overdueDebts->count() }} tagihan</div>
            </div>
        </div>
        <table class="table">
            <thead><tr><th>Pelanggan</th><th class="rpt-num">Sisa</th></tr></thead>
            <tbody id="overdueList">
                @forelse ($overdueDebts as $debt)
                    <tr><td>{{ $debt->customer?->name ?? '-' }}</td><td class="rpt-num">{{ $rp($debt->remaining_debt) }}</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">Tidak ada piutang overdue.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($overdueDebts->count() > 0)
            <div class="alert-action"><a href="{{ route('modules.reporting.outstanding') }}" class="btn ghost">Lihat Piutang &rarr;</a></div>
        @endif
    </section>
</div>

<div class="grid two" style="margin-top:16px">
    <section class="panel">
        <h2>Recent Sales</h2>
        <table class="table">
            <thead><tr><th>Receipt</th><th>Customer</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                @forelse ($recentSales as $sale)
                    <tr>
                        <td>{{ $sale->receipt_number }}</td>
                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td><span class="badge">{{ $sale->payment_status }}</span></td>
                        <td>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No sales yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <section class="panel">
        <h2>Low Stock</h2>
        <table class="table">
            <thead><tr><th>SKU</th><th>Item</th><th>Stock</th></tr></thead>
            <tbody>
                @forelse ($lowStock as $product)
                    <tr><td>{{ $product->sku }}</td><td>{{ $product->name }}</td><td class="danger">{{ $product->total_stock }}</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Stock levels are healthy.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<style>
    .grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .alert-panel { border-left: 4px solid var(--brand); }
    .alert-panel.danger { border-left-color: var(--danger); }
    .alert-header { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
    .alert-icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-size: 20px; }
    .alert-icon.danger { background: #fef2f2; color: var(--danger); }
    .alert-icon.warning { background: #fffbeb; color: #d97706; }
    .alert-count { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .alert-action { margin-top: 14px; }
    .alert-action .btn { width: 100%; }
    .dash-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.35); } 50% { box-shadow: 0 0 0 6px rgba(249, 115, 22, 0); } }
    @media (max-width: 1000px) { .grid.three { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .grid.three { grid-template-columns: 1fr; } }
</style>

<script>
    (function () {
        const POLL_INTERVAL = 30000; // 30 detik
        const url = '{{ route('api.alerts') }}';
        const fmt = new Intl.NumberFormat('id-ID');

        function renderList(el, rows, emptyText, buildRow) {
            if (!rows || rows.length === 0) {
                el.innerHTML = '<tr><td colspan="3" class="muted">' + emptyText + '</td></tr>';
                return;
            }
            el.innerHTML = rows.map(buildRow).join('');
        }

        function update(data) {
            const totalAlerts = (data.out_of_stock || 0) + (data.low_stock || 0) + (data.overdue_bills || 0);

            const dashCount = document.getElementById('dashLowStockCount');
            if (dashCount) dashCount.textContent = fmt.format((data.low_stock || 0) + (data.out_of_stock || 0));

            const outCount = document.getElementById('outOfStockCount');
            if (outCount) outCount.textContent = fmt.format(data.out_of_stock || 0) + ' produk';
            const outList = document.getElementById('outOfStockList');
            if (outList) renderList(outList, data.out_of_stock_items, 'Semua stok masih tersedia.', (p) => '<tr><td>' + p.sku + '</td><td>' + p.name + '</td></tr>');

            const lowCount = document.getElementById('lowStockCount');
            if (lowCount) lowCount.textContent = fmt.format(data.low_stock || 0) + ' produk';
            const lowList = document.getElementById('lowStockList');
            if (lowList) renderList(lowList, data.low_stock_items, 'Stok aman.', (p) => '<tr><td>' + p.sku + '</td><td>' + p.name + '</td><td class="danger">' + p.stock + '</td></tr>');

            const odCount = document.getElementById('overdueCount');
            if (odCount) odCount.textContent = fmt.format(data.overdue_bills || 0) + ' tagihan';
            const odList = document.getElementById('overdueList');
            if (odList) renderList(odList, data.overdue_items, 'Tidak ada piutang overdue.', (d) => '<tr><td>' + d.customer + '</td><td class="rpt-num">Rp ' + fmt.format(d.remaining) + '</td></tr>');

            const bell = document.getElementById('appBell');
            if (bell) {
                const badge = bell.querySelector('.bell-badge');
                if (badge) badge.textContent = totalAlerts > 99 ? '99+' : (totalAlerts || '');
                badge.classList.toggle('empty', !totalAlerts);
                bell.classList.toggle('dash-pulse', totalAlerts > 0);
            }

            if (totalAlerts > 0) {
                document.body.classList.add('has-alerts');
            } else {
                document.body.classList.remove('has-alerts');
            }
        }

        async function fetchAlerts() {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                update(data);
            } catch (e) {
                // silent fail agar tidak ganggu UX
            }
        }

        fetchAlerts();
        let timer = setInterval(fetchAlerts, POLL_INTERVAL);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(timer);
            } else {
                fetchAlerts();
                timer = setInterval(fetchAlerts, POLL_INTERVAL);
            }
        });
    })();
</script>
@endsection
