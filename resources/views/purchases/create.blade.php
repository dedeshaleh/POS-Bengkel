@extends('layouts.app')

@section('title', 'Add Purchase')
@section('subtitle', 'Create draft, on order, or direct closed purchase with many items.')

@section('content')
<style>
    .purchase-lines { min-width: 1180px; }
    .purchase-lines input { min-width: 96px; }
    .lookup-input { min-width: 260px; cursor: pointer; background:#fff; }
    .lookup-input:focus { outline: 0; border-color: #fdba74; box-shadow: 0 0 0 3px rgba(249, 115, 22, .18); }
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
    .totals { display:grid; gap:8px; justify-content:end; margin-top:14px; }
    .totals div { display:grid; grid-template-columns: 150px 170px; gap:12px; align-items:center; }
    @media (max-width: 760px) {
        .totals { justify-content:stretch; }
        .totals div { grid-template-columns: 1fr; gap:2px; }
    }
</style>

<section class="panel">
    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    <form method="post" action="{{ route('purchases.store') }}" id="purchaseForm">
        @csrf
        <div class="form-grid">
            <label>Invoice Number <input name="invoice_number" value="{{ old('invoice_number', 'PO-' . now()->format('Ymd-His')) }}" required></label>
            <label>Purchase Date <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required></label>
            <label>Supplier
                <input type="hidden" name="supplier_id" id="supplierId" value="{{ old('supplier_id') }}">
                <input class="lookup-input" id="supplierDisplay" value="Click to select supplier" readonly required data-supplier-open>
            </label>
            <label>Status
                <select name="status" required>
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="on_order" {{ old('status') === 'on_order' ? 'selected' : '' }}>On Order</option>
                    <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Close and Receive Full</option>
                </select>
            </label>
            <label>Discount <input type="number" step="0.01" min="0" name="discount_amount" id="discountAmount" value="{{ old('discount_amount', 0) }}"></label>
            <label class="full"><span><input type="checkbox" name="is_government_tax_collector" id="isGovernmentTaxCollector" value="1" style="width:auto" {{ old('is_government_tax_collector') ? 'checked' : '' }}> Buyer is Government / BUMN PPh 22 collector</span></label>
        </div>

        <div style="overflow-x:auto;margin-top:16px">
            <table class="table purchase-lines">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>UOM</th>
                        <th>Order Qty</th>
                        <th>Qty Base UOM</th>
                        <th>Price</th>
                        <th>Disc Type</th>
                        <th>Disc Value</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lineRows"></tbody>
            </table>
        </div>

        <div style="margin-top:12px">
            <button type="button" class="btn secondary" data-no-loading id="addLine">Add Row</button>
        </div>

        <div class="totals">
            <div><strong>Subtotal</strong><span id="subtotalText">Rp 0</span></div>
            <div><strong>Discount</strong><span id="discountText">Rp 0</span></div>
            <div><strong>PPN</strong><span id="ppnText">Rp 0</span></div>
            <div><strong>PPh / Tax Potong</strong><span id="withholdingText">Rp 0</span></div>
            <div><strong>DPP Barang / Jasa</strong><span id="dppSplitText">Rp 0 / Rp 0</span></div>
            <div><strong>Grand Total</strong><span id="grandText">Rp 0</span></div>
        </div>

        <div class="row-actions" style="margin-top:16px">
            <button class="btn">Save Purchase</button>
            <a class="btn secondary" href="{{ route('purchases.index') }}">Back</a>
        </div>
    </form>
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

<div class="lookup-modal" id="supplierModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 style="margin:0">Select Supplier</h2>
            <button type="button" class="btn secondary" data-no-loading id="supplierLookupClose">Close</button>
        </div>
        <div class="lookup-search"><input id="supplierLookupSearch" placeholder="Search..." autocomplete="off"></div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead><tr><th>Name</th><th>Tax Info</th></tr></thead>
                <tbody id="supplierLookupRows"></tbody>
            </table>
        </div>
        <div class="lookup-foot">
            <button type="button" class="btn secondary" data-no-loading id="supplierLookupPrev">Prev</button>
            <span class="muted" id="supplierLookupPage">Page 1 / 1</span>
            <button type="button" class="btn secondary" data-no-loading id="supplierLookupNext">Next</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const rows = document.getElementById('lineRows');
        const addLine = document.getElementById('addLine');
        const supplierId = document.getElementById('supplierId');
        const supplierDisplay = document.getElementById('supplierDisplay');
        const discountAmount = document.getElementById('discountAmount');
        const isGovernmentTaxCollector = document.getElementById('isGovernmentTaxCollector');
        const modal = document.getElementById('productModal');
        const lookupRows = document.getElementById('lookupRows');
        const lookupSearch = document.getElementById('lookupSearch');
        const lookupPage = document.getElementById('lookupPage');
        const lookupPrev = document.getElementById('lookupPrev');
        const lookupNext = document.getElementById('lookupNext');
        let activeRow = null;
        let page = 1;
        let lastPage = 1;
        let debounce;
        let supplierData = { ppn: 0, entityType: 'corporate', hasNpwp: false, pph21: 5 };

        function money(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
        }

        function makeRow() {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="product_id[]" class="product-id" required>
                    <input type="hidden" name="product_label[]" class="product-label-hidden">
                    <input type="hidden" class="line-item-type" value="">
                    <input class="lookup-input product-label" value="Click to select product" readonly required data-lookup-open>
                </td>
                <td><input name="purchased_uom_code[]" class="line-uom" value="PCS" required></td>
                <td><input type="number" step="1" min="1" name="purchased_qty[]" class="line-qty" value="1" required></td>
                <td><input type="number" step="1" min="1" name="qty_in_base_uom[]" class="line-base-qty" value="1" required></td>
                <td><input type="number" step="0.01" min="0" name="buy_price_per_purchased_uom[]" class="line-price" value="0" required></td>
                <td>
                    <select name="item_discount_type[]" class="line-discount-type">
                        <option value="none">No Disc</option>
                        <option value="fixed">Nominal</option>
                        <option value="percentage">Percent</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" name="item_discount_value[]" class="line-discount-value" value="0"></td>
                <td class="line-subtotal">Rp 0</td>
                <td><button type="button" class="btn" style="background:#b42318" data-no-loading data-remove-line>Remove</button><div class="muted line-price-source" style="margin-top:6px;font-size:12px">No price</div></td>
            `;
            return tr;
        }

        function recalc() {
            let subtotal = 0;
            let goodsDppBeforeHeaderDiscount = 0;
            let servicesDppBeforeHeaderDiscount = 0;
            rows.querySelectorAll('tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.line-qty').value || 0);
                const price = parseFloat(row.querySelector('.line-price').value || 0);
                const gross = qty * price;
                const discountType = row.querySelector('.line-discount-type').value;
                const discountValue = parseFloat(row.querySelector('.line-discount-value').value || 0);
                const discount = Math.min(gross, Math.max(0, discountType === 'percentage' ? gross * (discountValue / 100) : (discountType === 'fixed' ? discountValue : 0)));
                const lineTotal = Math.max(0, gross - discount);
                const itemType = (row.querySelector('.line-item-type').value || '').toUpperCase();
                if (itemType === 'SERVICE') {
                    servicesDppBeforeHeaderDiscount += lineTotal;
                } else {
                    goodsDppBeforeHeaderDiscount += lineTotal;
                }
                subtotal += lineTotal;
                row.querySelector('.line-subtotal').textContent = money(lineTotal);
            });
            const ppnRate = parseFloat(supplierData.ppn || 0);
            const discount = Math.min(subtotal, parseFloat(discountAmount.value || 0));
            const taxable = Math.max(0, subtotal - discount);
            const ppn = taxable * (ppnRate / 100);
            const goodsDpp = subtotal > 0 ? Math.max(0, goodsDppBeforeHeaderDiscount - (discount * (goodsDppBeforeHeaderDiscount / subtotal))) : 0;
            const servicesDpp = subtotal > 0 ? Math.max(0, servicesDppBeforeHeaderDiscount - (discount * (servicesDppBeforeHeaderDiscount / subtotal))) : 0;
            const entityType = supplierData.entityType || 'corporate';
            const hasNpwp = !!supplierData.hasNpwp;
            const withholdingArticles = [];
            let withholdingRate = 0;
            let withholding = 0;
            if (goodsDpp > 0 && isGovernmentTaxCollector.checked) {
                withholdingArticles.push('PPh 22 1.5%');
                withholdingRate = 1.5;
                withholding += goodsDpp * 0.015;
            }
            if (servicesDpp > 0 && entityType === 'corporate') {
                withholdingArticles.push('PPh 23 2%');
                withholdingRate = 2;
                withholding += servicesDpp * 0.02;
            } else if (servicesDpp > 0 && entityType === 'individual') {
                withholdingRate = parseFloat(supplierData.pph21 || 5);
                if (!hasNpwp) withholdingRate *= 1.2;
                withholdingArticles.push(`PPh 21 ${withholdingRate}%`);
                withholding += servicesDpp * (withholdingRate / 100);
            }
            const withholdingName = withholdingArticles.length ? withholdingArticles.join(' + ') : 'No PPh';
            const displayedRate = withholdingArticles.length === 1 ? withholdingRate : 0;
            document.getElementById('subtotalText').textContent = money(subtotal);
            document.getElementById('discountText').textContent = money(discount);
            document.getElementById('ppnText').textContent = `${money(ppn)} (${ppnRate}%)`;
            document.getElementById('withholdingText').textContent = `${money(withholding)} (${withholdingName}${withholdingArticles.length > 1 ? ' mixed' : ''})`;
            document.getElementById('dppSplitText').textContent = `${money(goodsDpp)} / ${money(servicesDpp)}`;
            document.getElementById('grandText').textContent = money(Math.max(0, taxable + ppn - withholding));
        }

        function openLookup(row) {
            activeRow = row;
            if (!supplierId.value) {
                alert('Select supplier first.');
                return;
            }
            page = 1;
            lookupSearch.value = '';
            modal.classList.add('open');
            loadProducts();
            setTimeout(() => lookupSearch.focus(), 50);
        }

        function closeLookup() {
            modal.classList.remove('open');
        }

        async function loadProducts() {
            lookupRows.innerHTML = '<tr><td colspan="2" class="muted">Loading products...</td></tr>';
            const params = new URLSearchParams({ page, q: lookupSearch.value, supplier_id: supplierId.value });
            const response = await fetch(`{{ route('purchases.lookup.products') }}?${params.toString()}`);
            const payload = await response.json();
            lastPage = payload.last_page || 1;
            lookupPage.textContent = `Page ${payload.current_page || 1} / ${lastPage}`;
            lookupPrev.disabled = (payload.current_page || 1) <= 1;
            lookupNext.disabled = (payload.current_page || 1) >= lastPage;

            if (!payload.data || payload.data.length === 0) {
                lookupRows.innerHTML = '<tr><td colspan="2" class="muted">No products found.</td></tr>';
                return;
            }

            lookupRows.innerHTML = payload.data.map(item => `
                <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}" data-uom="${escapeHtml(item.uom || 'PCS')}" data-item-type="${escapeHtml(item.item_type || '')}">
                    <td>${escapeHtml(item.label)}</td>
                    <td class="muted">${escapeHtml(item.description || '')}</td>
                </tr>
            `).join('');
        }

        function escapeHtml(value) {
            return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
        }

        async function chooseProduct(selected) {
            if (!activeRow) return;
            activeRow.querySelector('.product-id').value = selected.dataset.id;
            activeRow.querySelector('.product-label').value = selected.dataset.label;
            activeRow.querySelector('.product-label-hidden').value = selected.dataset.label;
            activeRow.querySelector('.line-item-type').value = selected.dataset.itemType || '';
            activeRow.querySelector('.line-uom').value = selected.dataset.uom || 'PCS';
            activeRow.querySelector('.line-base-qty').value = activeRow.querySelector('.line-qty').value || 1;

            const response = await fetch(`{{ url('/purchases/products') }}/${selected.dataset.id}/last-price?supplier_id=${encodeURIComponent(supplierId.value)}`);
            const payload = await response.json();
            activeRow.querySelector('.line-price').value = payload.price || 0;
            activeRow.querySelector('.line-price-source').textContent = payload.source || 'No price';
            if (payload.uom) activeRow.querySelector('.line-uom').value = payload.uom;
            closeLookup();
            recalc();
        }

        addLine.addEventListener('click', () => rows.appendChild(makeRow()));
        discountAmount.addEventListener('input', recalc);
        isGovernmentTaxCollector.addEventListener('change', recalc);
        rows.addEventListener('input', event => {
            if (event.target.matches('.line-qty')) {
                const row = event.target.closest('tr');
                if (row && (!row.querySelector('.line-base-qty').value || row.querySelector('.line-base-qty').value === '1')) {
                    row.querySelector('.line-base-qty').value = event.target.value;
                }
            }
            if (event.target.matches('.line-qty, .line-price, .line-base-qty, .line-discount-value, .line-discount-type')) recalc();
        });
        rows.addEventListener('change', event => {
            if (event.target.matches('.line-discount-type')) recalc();
        });
        rows.addEventListener('click', event => {
            const picker = event.target.closest('[data-lookup-open]');
            if (picker) openLookup(picker.closest('tr'));
            const remove = event.target.closest('[data-remove-line]');
            if (remove && rows.children.length > 1) {
                remove.closest('tr').remove();
                recalc();
            }
        });

        document.getElementById('lookupClose').addEventListener('click', closeLookup);
        modal.addEventListener('click', event => { if (event.target === modal) closeLookup(); });
        lookupRows.addEventListener('click', event => {
            const row = event.target.closest('tr[data-id]');
            if (row) chooseProduct(row);
        });
        lookupPrev.addEventListener('click', () => { if (page > 1) { page--; loadProducts(); } });
        lookupNext.addEventListener('click', () => { if (page < lastPage) { page++; loadProducts(); } });
        lookupSearch.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => { page = 1; loadProducts(); }, 300);
        });

        const supplierModal = document.getElementById('supplierModal');
        const supplierLookupRows = document.getElementById('supplierLookupRows');
        const supplierLookupSearch = document.getElementById('supplierLookupSearch');
        const supplierLookupPage = document.getElementById('supplierLookupPage');
        const supplierLookupPrev = document.getElementById('supplierLookupPrev');
        const supplierLookupNext = document.getElementById('supplierLookupNext');
        let supplierPage = 1;
        let supplierLastPage = 1;
        let supplierDebounce;

        async function loadSuppliers() {
            supplierLookupRows.innerHTML = '<tr><td colspan="2" class="muted">Loading suppliers...</td></tr>';
            const params = new URLSearchParams({ page: supplierPage, q: supplierLookupSearch.value });
            const response = await fetch(`{{ route('purchases.lookup.suppliers') }}?${params.toString()}`);
            const payload = await response.json();
            supplierLastPage = payload.last_page || 1;
            supplierLookupPage.textContent = `Page ${payload.current_page || 1} / ${supplierLastPage}`;
            supplierLookupPrev.disabled = (payload.current_page || 1) <= 1;
            supplierLookupNext.disabled = (payload.current_page || 1) >= supplierLastPage;
            if (!payload.data || payload.data.length === 0) {
                supplierLookupRows.innerHTML = '<tr><td colspan="2" class="muted">No suppliers found.</td></tr>';
                return;
            }
            supplierLookupRows.innerHTML = payload.data.map(item => `
                <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}" data-ppn="${escapeHtml(String(item.ppn || 0))}" data-entity-type="${escapeHtml(item.entity_type || 'corporate')}" data-has-npwp="${item.has_npwp ? 1 : 0}" data-pph21="${escapeHtml(String(item.pph21 || 5))}">
                    <td>${escapeHtml(item.label)}</td>
                    <td class="muted">${escapeHtml(item.description || '')}</td>
                </tr>
            `).join('');
        }

        function clearProductRows() {
            rows.querySelectorAll('tr').forEach(row => {
                row.querySelector('.product-id').value = '';
                row.querySelector('.product-label').value = 'Click to select product';
                row.querySelector('.product-label-hidden').value = '';
                row.querySelector('.line-item-type').value = '';
                row.querySelector('.line-price').value = 0;
                row.querySelector('.line-price-source').textContent = 'No price';
                row.querySelector('.line-subtotal').textContent = money(0);
            });
        }

        document.querySelector('[data-supplier-open]').addEventListener('click', function () {
            supplierPage = 1;
            supplierLookupSearch.value = '';
            supplierModal.classList.add('open');
            loadSuppliers();
            setTimeout(() => supplierLookupSearch.focus(), 50);
        });
        document.getElementById('supplierLookupClose').addEventListener('click', () => supplierModal.classList.remove('open'));
        supplierModal.addEventListener('click', event => { if (event.target === supplierModal) supplierModal.classList.remove('open'); });
        supplierLookupRows.addEventListener('click', event => {
            const row = event.target.closest('tr[data-id]');
            if (!row) return;
            supplierId.value = row.dataset.id;
            supplierDisplay.value = row.dataset.label;
            supplierData = {
                ppn: parseFloat(row.dataset.ppn || 0),
                entityType: row.dataset.entityType || 'corporate',
                hasNpwp: row.dataset.hasNpwp === '1',
                pph21: parseFloat(row.dataset.pph21 || 5),
            };
            clearProductRows();
            supplierModal.classList.remove('open');
            recalc();
        });
        supplierLookupPrev.addEventListener('click', () => { if (supplierPage > 1) { supplierPage--; loadSuppliers(); } });
        supplierLookupNext.addEventListener('click', () => { if (supplierPage < supplierLastPage) { supplierPage++; loadSuppliers(); } });
        supplierLookupSearch.addEventListener('input', () => {
            clearTimeout(supplierDebounce);
            supplierDebounce = setTimeout(() => { supplierPage = 1; loadSuppliers(); }, 300);
        });

        rows.appendChild(makeRow());
        recalc();
    })();
</script>
@endsection
