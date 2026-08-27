@php
    $current = request()->route()->getName();
    $showDates = $showDates ?? true;
    $tabs = [
        'modules.reporting.revenue' => ['Revenue', 'fa-solid fa-chart-line'],
        'modules.reporting.sales' => ['Penjualan', 'fa-solid fa-chart-column'],
        'modules.reporting.profit-loss' => ['Laba Rugi', 'fa-solid fa-scale-balanced'],
        'modules.reporting.stock' => ['Stok', 'fa-solid fa-boxes-stacked'],
        'modules.reporting.outstanding' => ['Piutang', 'fa-solid fa-file-invoice-dollar'],
        'modules.reporting.tax' => ['Pajak (PPN)', 'fa-solid fa-receipt'],
    ];
@endphp

<style>
    .rpt-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
    .rpt-tab { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 11px; background: #fff; border: 1px solid var(--line); color: #475569; font-weight: 650; font-size: 14px; box-shadow: var(--shadow-sm); transition: all .15s ease; }
    .rpt-tab:hover { border-color: #fed7aa; color: var(--brand-dark); }
    .rpt-tab.active { background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); color: #fff; border-color: transparent; box-shadow: 0 6px 16px -6px rgba(249, 115, 22, .55); }
    .rpt-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; margin-bottom: 20px; }
    .rpt-filter label { font-size: 12px; }
    .rpt-filter .field-date { display: grid; gap: 6px; }
    .rpt-filter input[type="date"] { min-width: 160px; }
    .rpt-presets { display: flex; gap: 6px; flex-wrap: wrap; }
    .rpt-presets button { background: #fff; border: 1px solid var(--line); color: #475569; border-radius: 999px; padding: 7px 13px; font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: none; }
    .rpt-presets button:hover { border-color: #fed7aa; color: var(--brand-dark); filter: none; transform: none; }
    .rpt-range-note { font-size: 13px; color: var(--muted); margin-left: auto; align-self: center; }
    .rpt-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    @media (max-width: 1100px) { .rpt-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) { .rpt-cards { grid-template-columns: 1fr; } .rpt-range-note { margin-left: 0; } }
    .rpt-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
    .rpt-pos { color: var(--success); font-weight: 700; }
    .rpt-neg { color: var(--danger); font-weight: 700; }
    .rpt-empty { padding: 28px; text-align: center; color: var(--muted); }
</style>

@php
    $dateParams = (isset($from) && isset($to)) ? ['from' => $from->toDateString(), 'to' => $to->toDateString()] : [];
@endphp

<div class="rpt-tabs">
    @foreach ($tabs as $route => [$label, $icon])
        <a class="rpt-tab {{ $current === $route ? 'active' : '' }}"
           href="{{ route($route, $dateParams) }}">
            <i class="{{ $icon }}"></i><span>{{ $label }}</span>
        </a>
    @endforeach
</div>

@if ($showDates && isset($from) && isset($to))
<form method="get" class="rpt-filter panel" id="rptFilterForm">
    <div class="field-date">
        <label for="rptFrom">Dari Tanggal</label>
        <input type="date" id="rptFrom" name="from" value="{{ $from->toDateString() }}">
    </div>
    <div class="field-date">
        <label for="rptTo">Sampai Tanggal</label>
        <input type="date" id="rptTo" name="to" value="{{ $to->toDateString() }}">
    </div>
    <button class="btn" type="submit"><i class="fa-solid fa-filter"></i><span>Terapkan</span></button>
    <div class="rpt-presets">
        <button type="button" data-preset="today">Hari Ini</button>
        <button type="button" data-preset="week">7 Hari</button>
        <button type="button" data-preset="month">Bulan Ini</button>
        <button type="button" data-preset="year">Tahun Ini</button>
    </div>
    <div class="rpt-range-note">
        <i class="fa-regular fa-calendar"></i>
        {{ $from->translatedFormat('d M Y') }} &ndash; {{ $to->translatedFormat('d M Y') }}
    </div>
</form>

<script>
    (function () {
        const form = document.getElementById('rptFilterForm');
        if (!form) return;
        const fromInput = form.querySelector('#rptFrom');
        const toInput = form.querySelector('#rptTo');
        const pad = (n) => String(n).padStart(2, '0');
        const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

        form.querySelectorAll('[data-preset]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const now = new Date();
                let from = new Date();
                const to = new Date();
                switch (this.dataset.preset) {
                    case 'today': from = new Date(); break;
                    case 'week': from.setDate(now.getDate() - 6); break;
                    case 'month': from = new Date(now.getFullYear(), now.getMonth(), 1); break;
                    case 'year': from = new Date(now.getFullYear(), 0, 1); break;
                }
                fromInput.value = fmt(from);
                toInput.value = fmt(to);
                form.submit();
            });
        });
    })();
</script>
@endif
