@extends('layouts.app')

@section('title', 'Open Cashier')
@section('subtitle', 'POS with unlimited items. Save draft or pay directly.')

@section('content')
<style>
    .pos-lines { min-width: 900px; }
    .pos-lines input { min-width: 80px; }
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
    .draft-card { border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
    .draft-card:hover { background:#f8fafc; }
    .barcode-scan { max-width: 420px; }
    .barcode-scan input { font-size: 15px; padding: 12px 14px; border: 2px solid #fed7aa; background: #fff7ed; }
    .barcode-scan input:focus { background: #fff; border-color: #f97316; }
    .voucher-box input { font-size: 14px; padding: 10px 12px; }
    .voucher-box #voucherInfo { display: block; font-size: 12px; }
    .voucher-box #voucherInfo.success { color: #166534; }
    .voucher-box #voucherInfo.error { color: #991b1b; }
</style>

<section class="panel">
    @if (session('status'))
        <div class="badge" style="background:#dcfce7;color:#166534;margin-bottom:12px">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px;white-space:pre-line">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    @if ($editingDraft)
        <div class="badge" style="background:#fef3c7;color:#92400e;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
            <span><i class="fa-solid fa-pen-to-square"></i> Editing draft: <strong>{{ $editingDraft->receipt_number }}</strong> — {{ $editingDraft->items->count() }} items</span>
            <a href="{{ route('modules.pos.open-cashier') }}" class="btn secondary" style="padding:4px 12px;font-size:12px">New Sale</a>
        </div>
    @endif

    <form method="post" action="{{ route('modules.pos.save-draft') }}" id="posForm">
        @csrf
        <input type="hidden" name="action" id="formAction" value="draft">
        <input type="hidden" name="header_discount_type" id="headerDiscountTypeHidden" value="fixed">
        <input type="hidden" name="sale_id" id="saleIdInput" value="{{ $editingDraft?->id ?? '' }}">

        <div class="form-grid">
            <label>Customer
                <div style="display:flex;gap:6px;align-items:center">
                    <input type="hidden" name="customer_id" id="posCustomerId">
                    <input type="text" class="lookup-input" id="posCustomerLookupInput" value="" placeholder="Walk-in Customer (click to select)" readonly data-lookup-open style="flex:1">
                    <button type="button" class="btn" style="background:#b42318;display:none" data-no-loading id="clearCustomerBtn" title="Hapus customer">X</button>
                </div>
            </label>
            <label>Discount
                <div style="display:flex;gap:6px;align-items:center">
                    <input type="number" step="0.01" min="0" name="header_discount" id="headerDiscount" value="0" style="flex:1">
                    <select id="headerDiscountType" style="width:70px">
                        <option value="fixed">Rp</option>
                        <option value="percentage">%</option>
                    </select>
                </div>
            </label>
            <label>Tax %
                <select name="tax_percentage" id="taxPercentage">
                    <option value="0">0% (No PPN)</option>
                    <option value="11">11% (PPN)</option>
                </select>
            </label>
        </div>

        <div style="overflow-x:auto;margin-top:16px">
            <table class="table pos-lines">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>UOM</th>
                        <th>Qty</th>
                        <th>Sell Price</th>
                        <th>Disc</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lineRows"></tbody>
            </table>
        </div>

        <div class="barcode-scan" style="margin-top:12px">
            <label>Scan Barcode / QR
                <input type="text" id="barcodeInput" placeholder="Scan barcode or QR code here..." autocomplete="off">
            </label>
        </div>

        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" class="btn secondary" data-no-loading id="addLine">+ Add Item</button>
        </div>

        <div class="totals">
            <div><strong>Subtotal</strong><span id="subtotalText">Rp 0</span></div>
            <div><strong>Voucher</strong><span id="voucherDiscountText">Rp 0</span></div>
            <div><strong>Discount</strong><span id="discountText">Rp 0</span></div>
            <div><strong>Tax</strong><span id="taxText">Rp 0</span></div>
            <div><strong>Grand Total</strong><span id="grandText">Rp 0</span></div>
        </div>

        <div class="voucher-box" style="margin-top:10px;max-width:420px">
            <input type="hidden" name="voucher_id" id="voucherId" value="">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="text" id="voucherCode" placeholder="Kode voucher..." style="flex:1" autocomplete="off">
                <button type="button" class="btn secondary" data-no-loading id="applyVoucherBtn">Apply</button>
                <button type="button" class="btn" style="background:#b42318;display:none" data-no-loading id="removeVoucherBtn">X</button>
            </div>
            <small id="voucherInfo" class="muted" style="display:none;margin-top:4px"></small>
        </div>

        <div class="row-actions" style="margin-top:16px;display:flex;gap:10px">
            <button type="submit" class="btn" id="payNowBtn">Pay Now</button>
            <button type="submit" class="btn secondary" id="saveDraftBtn">Save as Draft</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Back</a>
        </div>
    </form>
</section>

@if ($drafts->count() > 0)
<section class="panel" style="margin-top:20px">
    <h2>Drafts / Pending Sales</h2>
    @foreach ($drafts as $draft)
        <div class="draft-card">
            <div>
                <strong>{{ $draft->receipt_number }}</strong><br>
                <span class="muted">{{ $draft->items_count }} items | Rp {{ number_format($draft->grand_total, 0, ',', '.') }}</span>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('modules.pos.open-cashier', ['edit' => $draft->id]) }}" class="btn secondary" title="Edit draft items"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                <a href="{{ route('modules.pos.payment', $draft->id) }}" class="btn">Pay</a>
                <form method="post" action="{{ route('modules.pos.destroy-draft', $draft->id) }}" onsubmit="return confirm('Delete this draft? Stock will be released.')">
                    @csrf @method('DELETE')
                    <button class="btn" style="background:#b42318">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</section>
@endif

<div class="lookup-modal" id="customerModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 style="margin:0">Select Customer</h2>
            <button type="button" class="btn secondary" data-no-loading id="customerLookupClose">Close</button>
        </div>
        <div class="lookup-search"><input id="customerLookupSearch" placeholder="Search by name or phone..." autocomplete="off"></div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead><tr><th>Customer</th><th>Info</th></tr></thead>
                <tbody id="customerLookupRows"></tbody>
            </table>
        </div>
        <div class="lookup-foot">
            <button type="button" class="btn secondary" data-no-loading id="customerLookupPrev">Prev</button>
            <span class="muted" id="customerLookupPage">Page 1 / 1</span>
            <button type="button" class="btn secondary" data-no-loading id="customerLookupNext">Next</button>
            <button type="button" class="btn" data-no-loading id="quickAddCustomerBtn">+ New Customer</button>
        </div>
    </div>
</div>

<div class="lookup-modal" id="quickCustomerModal" aria-hidden="true">
    <div class="lookup-dialog" style="max-width:420px">
        <div class="lookup-head">
            <h2 style="margin:0">Add New Customer</h2>
            <button type="button" class="btn secondary" data-no-loading id="quickCustomerClose">Close</button>
        </div>
        <div style="padding:16px">
            <div class="form-grid">
                <label class="full">Name <input type="text" id="quickCustName" autocomplete="off"></label>
                <label>Phone <input type="text" id="quickCustPhone" autocomplete="off"></label>
                <label>License Plate <input type="text" id="quickCustPlate" autocomplete="off"></label>
            </div>
            <div id="quickCustError" class="muted" style="color:#991b1b;margin-top:8px;display:none"></div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="button" class="btn" data-no-loading id="quickCustSave">Save & Select</button>
                <button type="button" class="btn secondary" data-no-loading id="quickCustomerClose2">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="lookup-modal" id="productModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 style="margin:0">Select Product</h2>
            <button type="button" class="btn secondary" data-no-loading id="lookupClose">Close</button>
        </div>
        <div class="lookup-search"><input id="lookupSearch" placeholder="Search by name, SKU, or barcode..." autocomplete="off"></div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead><tr><th>Product</th><th>Info</th></tr></thead>
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
    const rows = document.getElementById('lineRows');
    const addLine = document.getElementById('addLine');
    const headerDiscount = document.getElementById('headerDiscount');
    const taxPercentage = document.getElementById('taxPercentage');
    const modal = document.getElementById('productModal');
    const lookupRows = document.getElementById('lookupRows');
    const lookupSearch = document.getElementById('lookupSearch');
    const lookupPage = document.getElementById('lookupPage');
    const lookupPrev = document.getElementById('lookupPrev');
    const lookupNext = document.getElementById('lookupNext');
    const formAction = document.getElementById('formAction');
    const payNowBtn = document.getElementById('payNowBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const barcodeInput = document.getElementById('barcodeInput');
    const voucherCodeInput = document.getElementById('voucherCode');
    const applyVoucherBtn = document.getElementById('applyVoucherBtn');
    const removeVoucherBtn = document.getElementById('removeVoucherBtn');
    const voucherIdInput = document.getElementById('voucherId');
    const voucherDiscountText = document.getElementById('voucherDiscountText');
    const voucherInfo = document.getElementById('voucherInfo');
    let voucherDiscount = 0;
    let activeRow = null;
    let page = 1;
    let lastPage = 1;
    let debounce;

    function money(value) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
    }

    function showAlert(title, text, icon) {
        return Swal.fire({ title, text, icon: icon || 'warning', confirmButtonColor: '#f97316' });
    }
    function showAlertMsg(msg, icon) {
        const parts = msg.split('\n');
        return showAlert(parts[0], parts.length > 1 ? parts.slice(1).join('\n') : '', icon || 'warning');
    }

    function makeRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="hidden" name="product_id[]" class="product-id" required>
                <input type="hidden" name="product_label[]" class="product-label-hidden">
                <input type="hidden" class="product-stock">
                <input type="hidden" name="uom_code[]" class="line-uom-code" value="">
                <input class="lookup-input product-label" value="Click to select product" readonly required data-lookup-open>
                <small class="stock-hint muted"></small>
            </td>
            <td><select class="line-uom-select" disabled style="min-width:70px"><option value="">—</option></select></td>
            <td><input type="number" step="1" min="1" name="qty[]" class="line-qty" value="1" required></td>
            <td><input type="number" step="0.01" min="0" name="selling_price[]" class="line-price" value="0" required></td>
            <td><input type="number" step="0.01" min="0" name="discount_amount[]" class="line-discount" value="0"></td>
            <td class="line-subtotal">Rp 0</td>
            <td><button type="button" class="btn" style="background:#b42318" data-no-loading data-remove-line>X</button></td>
        `;
        return tr;
    }

    function updateStockHint(row, stock, qty) {
        const hint = row.querySelector('.stock-hint');
        if (!hint) return;
        const stockNum = parseInt(stock || 0);
        const qtyNum = parseInt(qty || 1);
        if (stockNum <= 0) {
            hint.innerHTML = '<span class="danger">Stok habis</span>';
        } else if (qtyNum > stockNum) {
            hint.innerHTML = '<span class="danger">Stok tidak cukup (tersedia ' + stockNum + ')</span>';
        } else {
            hint.innerHTML = '<span class="muted">Stok tersedia: ' + stockNum + '</span>';
        }
    }

    function recalc() {
        let subtotal = 0;
        rows.querySelectorAll('tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.line-qty').value || 0);
            const price = parseFloat(row.querySelector('.line-price').value || 0);
            const discount = parseFloat(row.querySelector('.line-discount').value || 0);
            const stock = row.querySelector('.product-stock')?.value || 0;
            const lineTotal = Math.max(0, (qty * price) - discount);
            subtotal += lineTotal;
            row.querySelector('.line-subtotal').textContent = money(lineTotal);
            updateStockHint(row, stock, qty);
        });
        const rawDisc = parseFloat(headerDiscount.value || 0);
        const discType = document.getElementById('headerDiscountType').value;
        let headerDisc;
        if (discType === 'percentage') {
            headerDisc = subtotal * (rawDisc / 100);
        } else {
            headerDisc = rawDisc;
        }
        headerDisc = Math.min(subtotal, headerDisc);
        const totalDiscount = Math.min(subtotal, headerDisc + voucherDiscount);
        const taxable = Math.max(0, subtotal - totalDiscount);
        const taxRate = parseFloat(taxPercentage.value || 0);
        const tax = taxable * (taxRate / 100);
        const grand = Math.max(0, taxable + tax);
        document.getElementById('subtotalText').textContent = money(subtotal);
        document.getElementById('discountText').textContent = money(headerDisc);
        document.getElementById('taxText').textContent = `${money(tax)} (${taxRate}%)`;
        document.getElementById('grandText').textContent = money(grand);
    }

    function openLookup(row) {
        activeRow = row;
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
        const params = new URLSearchParams({ page, q: lookupSearch.value });
        const response = await fetch(`{{ route('modules.pos.lookup-products') }}?${params.toString()}`);
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
            <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}" data-uom="${escapeHtml(item.uom || 'PCS')}" data-price="${escapeHtml(String(item.price || 0))}" data-stock="${escapeHtml(String(item.stock || 0))}">
                <td>${escapeHtml(item.label)}</td>
                <td class="muted">${escapeHtml(item.description || '')}</td>
            </tr>
        `).join('');
    }

    async function chooseProduct(selected) {
        if (!activeRow) return;
        const stock = parseInt(selected.dataset.stock || 0);
        if (stock <= 0) {
            showAlert('Stok Habis', 'Barang tidak bisa diorder karena stok habis (0).', 'error');
            return;
        }

        const productId = selected.dataset.id;

        // Check if product already exists in another row
        let existing = null;
        rows.querySelectorAll('tr').forEach(row => {
            if (row !== activeRow && row.querySelector('.product-id').value === String(productId)) {
                existing = row;
            }
        });

        if (existing) {
            const qtyInput = existing.querySelector('.line-qty');
            const newQty = parseInt(qtyInput.value || 1) + 1;
            // Realtime stock check including drafts from other cashiers
            const check = await checkRealtimeStock(parseInt(productId), newQty);
            if (!check.ok) {
                let msg = `Stok "${check.name}" tidak mencukupi. Tersedia: ${check.stock}, diminta: ${newQty}.`;
                if (check.held > 0) msg += `\n(${check.held} unit sedang dipegang oleh draft/kasir lain)`;
                showAlertMsg(msg, 'error');
            } else {
                qtyInput.value = newQty;
                existing.querySelector('.product-stock').value = check.stock;
                updateStockHint(existing, check.stock, newQty);
            }
            // Remove the empty active row and close lookup regardless
            if (!activeRow.querySelector('.product-id').value) {
                activeRow.remove();
            }
            activeRow = null;
            closeLookup();
            recalc();
            return;
        }

        // Realtime stock check for new item
        const check = await checkRealtimeStock(parseInt(productId), 1);
        if (!check.ok) {
            let msg = `Stok "${check.name}" tidak mencukupi. Tersedia: ${check.stock}, diminta: 1.`;
            if (check.held > 0) msg += `\n(${check.held} unit sedang dipegang oleh draft/kasir lain)`;
            showAlertMsg(msg, 'error');
            // Remove empty active row on stock failure
            if (!activeRow.querySelector('.product-id').value) {
                activeRow.remove();
            }
            activeRow = null;
            closeLookup();
            recalc();
            return;
        }

        activeRow.querySelector('.product-id').value = productId;
        activeRow.querySelector('.product-label').value = selected.dataset.label;
        activeRow.querySelector('.product-label-hidden').value = selected.dataset.label;
        activeRow.querySelector('.product-stock').value = check.stock;
        activeRow.querySelector('.line-price').value = selected.dataset.price || 0;

        // Fetch available UOMs for this product and populate the dropdown
        try {
            const uomRes = await fetch(`{{ route('modules.pos.lookup-uoms', ['product' => 'ID_PLACEHOLDER']) }}`.replace('ID_PLACEHOLDER', productId));
            const uomData = await uomRes.json();
            const uomSelect = activeRow.querySelector('.line-uom-select');
            const uomHidden = activeRow.querySelector('.line-uom-code');
            uomSelect.innerHTML = (uomData.uoms || []).map(u =>
                `<option value="${escapeHtml(u.code)}" data-factor="${u.factor_to_base}">${escapeHtml(u.code)}</option>`
            ).join('');
            if (uomData.base_uom) {
                uomSelect.value = uomData.base_uom;
                uomHidden.value = uomData.base_uom;
            }
            uomSelect.disabled = false;
            uomSelect.addEventListener('change', function () {
                uomHidden.value = this.value;
            });
        } catch (e) {
            // UOM lookup failed — default to base UOM from product data
            activeRow.querySelector('.line-uom-code').value = selected.dataset.uom || 'PCS';
        }

        updateStockHint(activeRow, check.stock, activeRow.querySelector('.line-qty').value);
        activeRow = null;
        closeLookup();
        recalc();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({ '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#039;' }[char]));
    }

    async function addRowWithProduct(productId, label, price, stock) {
        // Check if product already in cart
        let existing = null;
        rows.querySelectorAll('tr').forEach(row => {
            if (row.querySelector('.product-id').value === String(productId)) {
                existing = row;
            }
        });
        if (existing) {
            const qtyInput = existing.querySelector('.line-qty');
            const newQty = parseInt(qtyInput.value || 1) + 1;
            const check = await checkRealtimeStock(parseInt(productId), newQty);
            if (!check.ok) {
                let msg = `Stok "${check.name}" tidak mencukupi. Tersedia: ${check.stock}, diminta: ${newQty}.`;
                if (check.held > 0) msg += `\n(${check.held} unit sedang dipegang oleh draft/kasir lain)`;
                showAlertMsg(msg, 'error');
                return;
            }
            qtyInput.value = newQty;
            existing.querySelector('.product-stock').value = check.stock;
            updateStockHint(existing, check.stock, newQty);
            recalc();
            return;
        }
        // Realtime stock check for new item
        const check = await checkRealtimeStock(parseInt(productId), 1);
        if (!check.ok) {
            let msg = `Stok "${check.name}" tidak mencukupi. Tersedia: ${check.stock}, diminta: 1.`;
            if (check.held > 0) msg += `\n(${check.held} unit sedang dipegang oleh draft/kasir lain)`;
            showAlertMsg(msg, 'error');
            return;
        }
        const tr = makeRow();
        tr.querySelector('.product-id').value = productId;
        tr.querySelector('.product-label').value = label;
        tr.querySelector('.product-label-hidden').value = label;
        tr.querySelector('.product-stock').value = check.stock;
        tr.querySelector('.line-price').value = price || 0;
        updateStockHint(tr, check.stock, 1);
        rows.appendChild(tr);
        recalc();
    }

    async function lookupByBarcode(barcode) {
        const params = new URLSearchParams({ q: barcode });
        const response = await fetch(`{{ route('modules.pos.lookup-products') }}?${params.toString()}`);
        const payload = await response.json();
        if (payload.data && payload.data.length > 0) {
            const item = payload.data[0];
            addRowWithProduct(item.value, item.label, item.price, item.stock);
            barcodeInput.value = '';
            barcodeInput.focus();
            return true;
        }
        return false;
    }

    // Event listeners
    addLine.addEventListener('click', () => {
        const tr = makeRow();
        rows.appendChild(tr);
        openLookup(tr);
    });

    // Remove line: allow removing last row too (empty cart is OK)
    rows.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-line]');
        if (remove) {
            remove.closest('tr').remove();
            recalc();
        }
    });

    headerDiscount.addEventListener('input', recalc);
    taxPercentage.addEventListener('change', recalc);

    rows.addEventListener('input', event => {
        if (event.target.matches('.line-qty, .line-price, .line-discount')) recalc();
    });

    rows.addEventListener('click', event => {
        const picker = event.target.closest('[data-lookup-open]');
        if (picker) openLookup(picker.closest('tr'));
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

    // Realtime stock check before submit - server-side validation including drafts from other cashiers
    async function checkStockBeforeSubmit() {
        const items = rows.querySelectorAll('tr');
        if (items.length === 0) {
            showAlert('Cart Kosong', 'Harap masukkan barang terlebih dahulu.', 'warning');
            return false;
        }

        const payload = [];
        for (const row of items) {
            const productId = row.querySelector('.product-id').value;
            const qty = parseInt(row.querySelector('.line-qty').value || 0);
            if (!productId) {
                showAlert('Barang Belum Dipilih', 'Harap pilih barang untuk semua baris.', 'warning');
                return false;
            }
            payload.push({ product_id: parseInt(productId), qty: qty });
        }

        try {
            const res = await fetch('{{ route("modules.pos.check-stock") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ items: payload }),
            });

            const data = await res.json();
            if (!res.ok) {
                showAlertMsg(data.errors.join('\n'), 'error');
                return false;
            }
        } catch (e) {
            showAlert('Error', 'Gagal memeriksa stok. Coba lagi.', 'error');
            return false;
        }
        return true;
    }

    // Realtime stock check when adding items
    async function checkRealtimeStock(productId, requestedQty) {
        try {
            const res = await fetch(`{{ route('modules.pos.realtime-stock') }}?product_id=${productId}`);
            const data = await res.json();
            if (data.effective_stock < requestedQty) {
                return { ok: false, stock: data.effective_stock, name: data.name, held: data.held_by_drafts };
            }
            return { ok: true, stock: data.effective_stock, held: data.held_by_drafts };
        } catch (e) {
            return { ok: true, stock: 0, held: 0 };
        }
    }

    // Pay Now vs Save as Draft
    payNowBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        const ok = await checkStockBeforeSubmit();
        if (!ok) return;
        formAction.value = 'pay';
        document.getElementById('posForm').submit();
    });

    saveDraftBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        const ok = await checkStockBeforeSubmit();
        if (!ok) return;
        formAction.value = 'draft';
        document.getElementById('posForm').submit();
    });

    // Barcode scanner
    barcodeInput.addEventListener('keydown', async function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = this.value.trim();
            if (!barcode) return;
            const found = await lookupByBarcode(barcode);
            if (!found) {
                showAlert('Produk Tidak Ditemukan', 'Barcode tidak ditemukan: ' + barcode, 'error');
            }
        }
    });

    // Voucher apply/remove
    function getCartData() {
        let subtotal = 0;
        const productIds = [];
        const lineSubtotals = [];
        rows.querySelectorAll('tr').forEach(row => {
            const pid = row.querySelector('.product-id').value;
            if (!pid) return;
            const qty = parseFloat(row.querySelector('.line-qty').value || 0);
            const price = parseFloat(row.querySelector('.line-price').value || 0);
            const disc = parseFloat(row.querySelector('.line-discount').value || 0);
            const lineTotal = Math.max(0, (qty * price) - disc);
            subtotal += lineTotal;
            productIds.push(parseInt(pid));
            lineSubtotals.push(lineTotal);
        });
        return { subtotal, productIds, lineSubtotals };
    }

    function resetVoucher() {
        voucherDiscount = 0;
        voucherIdInput.value = '';
        voucherDiscountText.textContent = money(0);
        voucherInfo.style.display = 'none';
        voucherInfo.textContent = '';
        voucherInfo.className = 'muted';
        removeVoucherBtn.style.display = 'none';
        voucherCodeInput.value = '';
        recalc();
    }

    applyVoucherBtn.addEventListener('click', async function() {
        const code = voucherCodeInput.value.trim();
        if (!code) {
            voucherInfo.style.display = 'block';
            voucherInfo.textContent = 'Masukkan kode voucher dulu.';
            voucherInfo.className = 'error';
            return;
        }

        const cart = getCartData();
        if (cart.productIds.length === 0) {
            voucherInfo.style.display = 'block';
            voucherInfo.textContent = 'Tambahkan item dulu sebelum apply voucher.';
            voucherInfo.className = 'error';
            return;
        }

        voucherInfo.style.display = 'block';
        voucherInfo.textContent = 'Checking voucher...';
        voucherInfo.className = 'muted';

        try {
            const res = await fetch('{{ route("modules.pos.apply-voucher") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    code: code,
                    subtotal: cart.subtotal,
                    product_ids: cart.productIds,
                    line_subtotals: cart.lineSubtotals,
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                voucherDiscount = 0;
                voucherIdInput.value = '';
                voucherDiscountText.textContent = money(0);
                voucherInfo.textContent = data.error || 'Gagal apply voucher';
                voucherInfo.className = 'error';
                removeVoucherBtn.style.display = 'none';
                recalc();
                return;
            }

            voucherDiscount = data.discount_amount;
            voucherIdInput.value = data.voucher_id;
            voucherDiscountText.textContent = money(data.discount_amount);
            const scopeLabel = data.scope_type === 'item' ? 'Item tertentu' : 'Transaksi';
            const typeLabel = data.discount_type === 'percentage' ? '%' : 'Rp';
            voucherInfo.textContent = (data.name || data.code) + ' — ' + scopeLabel + ' — Diskon: ' + money(data.discount_amount);
            voucherInfo.className = 'success';
            removeVoucherBtn.style.display = '';
            recalc();
        } catch (e) {
            voucherInfo.textContent = 'Error applying voucher';
            voucherInfo.className = 'error';
        }
    });

    voucherCodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyVoucherBtn.click();
        }
    });

    removeVoucherBtn.addEventListener('click', function() {
        resetVoucher();
    });

    // Customer lookup
    const customerModal = document.getElementById('customerModal');
    const customerLookupRows = document.getElementById('customerLookupRows');
    const customerLookupSearch = document.getElementById('customerLookupSearch');
    const customerLookupPage = document.getElementById('customerLookupPage');
    const customerLookupPrev = document.getElementById('customerLookupPrev');
    const customerLookupNext = document.getElementById('customerLookupNext');
    const posCustomerId = document.getElementById('posCustomerId');
    const posCustomerLookupInput = document.getElementById('posCustomerLookupInput');
    let custPage = 1;
    let custLastPage = 1;
    let custDebounce;

    function openCustomerLookup() {
        custPage = 1;
        customerLookupSearch.value = '';
        customerModal.classList.add('open');
        loadCustomers();
        setTimeout(() => customerLookupSearch.focus(), 50);
    }

    function closeCustomerLookup() {
        customerModal.classList.remove('open');
    }

    async function loadCustomers() {
        customerLookupRows.innerHTML = '<tr><td colspan="2" class="muted">Loading customers...</td></tr>';
        const params = new URLSearchParams({ page: custPage, q: customerLookupSearch.value });
        const response = await fetch(`{{ route('modules.pos.lookup-customers') }}?${params.toString()}`);
        const payload = await response.json();
        custLastPage = payload.last_page || 1;
        customerLookupPage.textContent = `Page ${payload.current_page || 1} / ${custLastPage}`;
        customerLookupPrev.disabled = (payload.current_page || 1) <= 1;
        customerLookupNext.disabled = (payload.current_page || 1) >= custLastPage;

        if (!payload.data || payload.data.length === 0) {
            customerLookupRows.innerHTML = '<tr><td colspan="2" class="muted">No customers found.</td></tr>';
            return;
        }

        customerLookupRows.innerHTML = payload.data.map(item => `
            <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}" data-debt="${escapeHtml(String(item.debt || 0))}">
                <td>${escapeHtml(item.label)}</td>
                <td class="muted">${escapeHtml(item.description || '')}</td>
            </tr>
        `).join('');
    }

    function chooseCustomer(selected) {
        posCustomerId.value = selected.dataset.id;
        posCustomerLookupInput.value = selected.dataset.label;
        clearCustomerBtn.style.display = '';
        closeCustomerLookup();
    }

    posCustomerLookupInput.addEventListener('click', openCustomerLookup);

    clearCustomerBtn.addEventListener('click', function() {
        posCustomerId.value = '';
        posCustomerLookupInput.value = '';
        clearCustomerBtn.style.display = 'none';
    });

    document.getElementById('customerLookupClose').addEventListener('click', closeCustomerLookup);
    customerModal.addEventListener('click', event => { if (event.target === customerModal) closeCustomerLookup(); });

    customerLookupRows.addEventListener('click', event => {
        const row = event.target.closest('tr[data-id]');
        if (row) chooseCustomer(row);
    });

    customerLookupPrev.addEventListener('click', () => { if (custPage > 1) { custPage--; loadCustomers(); } });
    customerLookupNext.addEventListener('click', () => { if (custPage < custLastPage) { custPage++; loadCustomers(); } });

    // Quick Add Customer
    const quickCustomerModal = document.getElementById('quickCustomerModal');
    const quickCustName = document.getElementById('quickCustName');
    const quickCustPhone = document.getElementById('quickCustPhone');
    const quickCustPlate = document.getElementById('quickCustPlate');
    const quickCustError = document.getElementById('quickCustError');
    const quickCustSave = document.getElementById('quickCustSave');

    function openQuickCustomer() {
        quickCustName.value = '';
        quickCustPhone.value = '';
        quickCustPlate.value = '';
        quickCustError.style.display = 'none';
        quickCustomerModal.classList.add('open');
        setTimeout(() => quickCustName.focus(), 50);
    }

    function closeQuickCustomer() {
        quickCustomerModal.classList.remove('open');
    }

    document.getElementById('quickAddCustomerBtn').addEventListener('click', openQuickCustomer);
    document.getElementById('quickCustomerClose').addEventListener('click', closeQuickCustomer);
    document.getElementById('quickCustomerClose2').addEventListener('click', closeQuickCustomer);
    quickCustomerModal.addEventListener('click', event => { if (event.target === quickCustomerModal) closeQuickCustomer(); });

    quickCustSave.addEventListener('click', async function() {
        const name = quickCustName.value.trim();
        if (!name) {
            quickCustError.textContent = 'Nama customer wajib diisi.';
            quickCustError.style.display = 'block';
            return;
        }

        try {
            const res = await fetch('{{ route("modules.pos.quick-add-customer") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    name: name,
                    phone: quickCustPhone.value.trim(),
                    license_plate: quickCustPlate.value.trim(),
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                quickCustError.textContent = data.message || 'Gagal menyimpan customer.';
                quickCustError.style.display = 'block';
                return;
            }

            posCustomerId.value = data.value;
            posCustomerLookupInput.value = data.label;
            clearCustomerBtn.style.display = '';
            closeQuickCustomer();
            closeCustomerLookup();
        } catch (e) {
            quickCustError.textContent = 'Error saving customer.';
            quickCustError.style.display = 'block';
        }
    });

    quickCustName.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); quickCustPhone.focus(); } });
    quickCustPhone.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); quickCustPlate.focus(); } });
    quickCustPlate.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); quickCustSave.click(); } });

    customerLookupSearch.addEventListener('input', () => {
        clearTimeout(custDebounce);
        custDebounce = setTimeout(() => { custPage = 1; loadCustomers(); }, 300);
    });

    // Load editing draft data if present
    @php
        $draftJson = $editingDraft ? json_encode([
            'customer' => $editingDraft->customer ? ['id' => $editingDraft->customer->id, 'label' => $editingDraft->customer->name . ($editingDraft->customer->license_plate ? " ({$editingDraft->customer->license_plate})" : '')] : null,
            'header_discount' => (float) $editingDraft->discount_amount,
            'tax_percentage' => (float) $editingDraft->tax_percentage,
            'items' => $editingDraft->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'label' => $item->product ? "{$item->product->sku} - {$item->product->name}" : 'Deleted',
                    'price' => (float) $item->base_selling_price,
                    'qty' => $item->qty,
                    'discount' => (float) $item->discount_amount,
                    'stock' => $item->product ? $item->product->total_stock + $item->qty : 0,
                ];
            })->toArray(),
        ]) : 'null';
    @endphp
    @if ($editingDraft)
        const draftData = {!! $draftJson !!};
        // Load customer
        if (draftData.customer) {
            posCustomerId.value = draftData.customer.id;
            posCustomerLookupInput.value = draftData.customer.label;
            clearCustomerBtn.style.display = '';
        }
        // Load header discount and tax
        headerDiscount.value = draftData.header_discount || 0;
        taxPercentage.value = draftData.tax_percentage || 0;
        // Load items
        draftData.items.forEach(async item => {
            const tr = makeRow();
            tr.querySelector('.product-id').value = item.product_id;
            tr.querySelector('.product-label').value = item.label;
            tr.querySelector('.product-label-hidden').value = item.label;
            tr.querySelector('.product-stock').value = item.stock;
            tr.querySelector('.line-price').value = item.price;
            tr.querySelector('.line-qty').value = item.qty;
            tr.querySelector('.line-discount').value = item.discount;
            // Fetch UOMs for this product
            try {
                const uomRes = await fetch(`{{ route('modules.pos.lookup-uoms', ['product' => 'ID_PLACEHOLDER']) }}`.replace('ID_PLACEHOLDER', item.product_id));
                const uomData = await uomRes.json();
                const uomSelect = tr.querySelector('.line-uom-select');
                const uomHidden = tr.querySelector('.line-uom-code');
                uomSelect.innerHTML = (uomData.uoms || []).map(u =>
                    `<option value="${escapeHtml(u.code)}" data-factor="${u.factor_to_base}">${escapeHtml(u.code)}</option>`
                ).join('');
                if (uomData.base_uom) {
                    uomSelect.value = uomData.base_uom;
                    uomHidden.value = uomData.base_uom;
                }
                uomSelect.disabled = false;
                uomSelect.addEventListener('change', function () {
                    uomHidden.value = this.value;
                });
            } catch (e) {
                tr.querySelector('.line-uom-code').value = 'PCS';
            }
            updateStockHint(tr, item.stock, item.qty);
            rows.appendChild(tr);
            recalc();
        });
    @else
    // Start with no rows - empty cart
    recalc();
    @endif

    // Auto-focus barcode scanner on load and keep focused
    if (barcodeInput) {
        barcodeInput.focus();
        // Refocus barcode when clicking elsewhere (unless user is in another input/select)
        document.addEventListener('click', function(e) {
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA' || e.target.closest('.modal') || e.target.closest('button')) return;
            setTimeout(() => barcodeInput.focus(), 50);
        });
        // Refocus after modal closes
        const observer = new MutationObserver(() => {
            if (!modal.classList.contains('open') && !customerModal.classList.contains('open')) {
                const active = document.activeElement;
                if (active !== barcodeInput && active.tagName !== 'INPUT' && active.tagName !== 'SELECT') {
                    barcodeInput.focus();
                }
            }
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }

    // Recalc when discount type changes
    const discTypeSelect = document.getElementById('headerDiscountType');
    const discTypeHidden = document.getElementById('headerDiscountTypeHidden');
    discTypeSelect.addEventListener('change', function() {
        discTypeHidden.value = this.value;
        recalc();
    });
})();
</script>
@endsection
