@extends('layouts.app')

@section('title', 'View Supplier')
@section('subtitle', 'Supplier detail.')

@section('content')
<style>
    .lookup-input { cursor: pointer; background:#fff; }
    .lookup-modal { position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, .45); padding: 18px; }
    .lookup-modal.open { display: flex; }
    .lookup-dialog { width: min(780px, 100%); max-height: min(720px, 92vh); overflow: hidden; background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 22px 70px rgba(15, 23, 42, .28); display: grid; grid-template-rows: auto auto minmax(0, 1fr) auto; }
    .lookup-head, .lookup-foot { padding: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lookup-head { border-bottom: 1px solid #e2e8f0; }
    .lookup-foot { border-top: 1px solid #e2e8f0; }
    .lookup-search { padding: 12px 14px; }
    .lookup-body { overflow:auto; padding: 0 14px 14px; }
    .lookup-table tr { cursor:pointer; }
    .lookup-table tr:hover td { background:#fff7ed; }
</style>

<section class="panel" style="max-width:860px">
    <h2>{{ $supplier->company_name }}</h2>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Contact</th><td>{{ $supplier->contact_person ?? '-' }}</td></tr>
            <tr><th>Phone</th><td>{{ $supplier->phone ?? '-' }}</td></tr>
            <tr><th>Email</th><td>{{ $supplier->email ?? '-' }}</td></tr>
            <tr><th>NPWP</th><td>{{ $supplier->tax_id_npwp ?? '-' }}</td></tr>
            <tr><th>Entity Type</th><td>{{ str($supplier->entity_type ?? 'corporate')->title() }}</td></tr>
            <tr><th>PPh 21 %</th><td>{{ number_format($supplier->pph21_percentage ?? 0, 2, ',', '.') }}%</td></tr>
            <tr><th>PPN</th><td>{{ $supplier->is_ppn_enabled ? $supplier->ppn_percentage . '%' : 'No PPN' }}</td></tr>
            <tr><th>Bank Name</th><td>{{ $supplier->bank_name ?? '-' }}</td></tr>
            <tr><th>Account Name</th><td>{{ $supplier->bank_account_name ?? '-' }}</td></tr>
            <tr><th>Account Number</th><td>{{ $supplier->bank_account_number ?? '-' }}</td></tr>
            <tr><th>Cabang Bank</th><td>{{ $supplier->bank_branch ?? '-' }}</td></tr>
            <tr><th>Bank Note</th><td>{{ $supplier->bank_account_info ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $supplier->is_active ? 'Active' : 'NonAktif' }}</td></tr>
            <tr><th>Address</th><td>{{ $supplier->address ?? '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <a class="btn secondary" href="{{ route('master.suppliers.edit', $supplier) }}">Edit</a>
        <a class="btn secondary" href="{{ route('master.suppliers.index') }}">Back</a>
    </div>
</section>

<section class="panel" style="max-width:860px;margin-top:16px">
    <h2>Products Sold by Supplier</h2>
    <form method="post" action="{{ route('master.suppliers.products.attach', $supplier) }}" class="form-grid" style="margin-bottom:14px">
        @csrf
        <input type="hidden" name="product_id" id="product_id" required>
        <label>Product <input class="lookup-input" id="product_display" value="Click to select product" readonly data-lookup-open required></label>
        <label>Supplier SKU <input name="supplier_sku" placeholder="Optional supplier code"></label>
        <div style="display:flex;align-items:end"><button class="btn">Add Product</button></div>
    </form>

    <table class="table">
        <thead><tr><th>Product</th><th>Supplier SKU</th><th>Type</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($supplier->supplierProducts as $mapping)
                <tr>
                    <td>{{ $mapping->product?->sku }} - {{ $mapping->product?->name }}</td>
                    <td>{{ $mapping->supplier_sku ?: '-' }}</td>
                    <td>{{ $mapping->product?->item_type_code }}</td>
                    <td>
                        <form method="post" action="{{ route('master.suppliers.products.detach', [$supplier, $mapping->product]) }}">
                            @csrf
                            @method('delete')
                            <button class="btn" style="background:#b42318">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No product mapping yet. Purchase item lookup will be empty until products are mapped.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="lookup-modal" id="productModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 style="margin:0">Select Product</h2>
            <button type="button" class="btn secondary" data-no-loading id="lookupClose">Close</button>
        </div>
        <div class="lookup-search"><input id="lookupSearch" placeholder="Search..." autocomplete="off"></div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead><tr><th>Name</th><th>Code / Info</th></tr></thead>
                <tbody id="lookupRows"></tbody>
            </table>
        </div>
        <div class="lookup-foot">
            <button type="button" class="btn secondary" data-no-loading id="lookupPrev">Prev</button>
            <span class="muted" id="lookupPage">Page 1 / 1</span>
            <button type="button" class="btn secondary" data-no-loading id="lookupNext">Next</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('productModal');
        const rows = document.getElementById('lookupRows');
        const search = document.getElementById('lookupSearch');
        const pageLabel = document.getElementById('lookupPage');
        const prev = document.getElementById('lookupPrev');
        const next = document.getElementById('lookupNext');
        let page = 1;
        let lastPage = 1;
        let debounce;

        function escapeHtml(value) {
            return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
        }

        async function loadRows() {
            rows.innerHTML = '<tr><td colspan="2" class="muted">Loading products...</td></tr>';
            const params = new URLSearchParams({ page, q: search.value });
            const response = await fetch(`{{ route('master.suppliers.lookup.products', $supplier) }}?${params.toString()}`);
            const payload = await response.json();
            lastPage = payload.last_page || 1;
            pageLabel.textContent = `Page ${payload.current_page || 1} / ${lastPage}`;
            prev.disabled = (payload.current_page || 1) <= 1;
            next.disabled = (payload.current_page || 1) >= lastPage;
            if (!payload.data || payload.data.length === 0) {
                rows.innerHTML = '<tr><td colspan="2" class="muted">No products found.</td></tr>';
                return;
            }
            rows.innerHTML = payload.data.map(item => `
                <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}">
                    <td>${escapeHtml(item.label)}</td>
                    <td class="muted">${escapeHtml(item.description || '')}</td>
                </tr>
            `).join('');
        }

        function openLookup() {
            page = 1;
            search.value = '';
            modal.classList.add('open');
            loadRows();
            setTimeout(() => search.focus(), 50);
        }

        function closeLookup() {
            modal.classList.remove('open');
        }

        document.querySelector('[data-lookup-open]')?.addEventListener('click', openLookup);
        document.getElementById('lookupClose').addEventListener('click', closeLookup);
        modal.addEventListener('click', event => { if (event.target === modal) closeLookup(); });
        rows.addEventListener('click', event => {
            const row = event.target.closest('tr[data-id]');
            if (!row) return;
            document.getElementById('product_id').value = row.dataset.id;
            document.getElementById('product_display').value = row.dataset.label;
            closeLookup();
        });
        prev.addEventListener('click', () => { if (page > 1) { page--; loadRows(); } });
        next.addEventListener('click', () => { if (page < lastPage) { page++; loadRows(); } });
        search.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => { page = 1; loadRows(); }, 300);
        });
    })();
</script>
@endsection
