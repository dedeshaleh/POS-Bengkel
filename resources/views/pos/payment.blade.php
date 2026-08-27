@extends('layouts.app')

@section('title', 'Payment')
@section('subtitle', 'Complete payment for sale ' . $sale->receipt_number)

@section('content')
<style>
    .payment-summary { display:grid; gap:8px; justify-content:end; margin-bottom:20px; }
    .payment-summary div { display:grid; grid-template-columns: 150px 170px; gap:12px; align-items:center; }
    @media (max-width: 760px) {
        .payment-summary { justify-content:stretch; }
        .payment-summary div { grid-template-columns: 1fr; gap:2px; }
    }
    .lookup-input { min-width: 260px; cursor: pointer; background:#fff; }
    .lookup-input:focus { outline: 0; border-color: #fdba74; box-shadow: 0 0 0 3px rgba(249, 115, 22, .18); }
    .lookup-modal { position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, .45); padding: 18px; }
    .lookup-modal.open { display: flex; }
    .lookup-dialog { width: min(600px, 100%); max-height: min(600px, 92vh); overflow: hidden; background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 22px 70px rgba(15, 23, 42, .28); display: grid; grid-template-rows: auto auto minmax(0, 1fr) auto; }
    .lookup-head, .lookup-foot { padding: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lookup-head { border-bottom: 1px solid #e2e8f0; }
    .lookup-foot { border-top: 1px solid #e2e8f0; }
    .lookup-search { padding: 12px 14px; }
    .lookup-body { overflow:auto; padding: 0 14px 14px; }
    .lookup-table tr { cursor:pointer; }
    .lookup-table tr:hover td { background:#fff7ed; }

    .pay-type-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:16px; }
    .pay-type-card { border:2px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; cursor:pointer; transition: all .15s; }
    .pay-type-card i { font-size:28px; margin-bottom:6px; display:block; }
    .pay-type-card span { font-weight:600; font-size:14px; }
    .pay-type-card.active { border-color:#f97316; background:#fff7ed; color:#c2410c; }
    .pay-type-card:hover { border-color:#fdba74; }

    .quick-cash-box { margin-top:12px; }
    .quick-cash-display { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
    .quick-cash-display input { font-size:20px; font-weight:700; text-align:right; padding:12px 14px; border:2px solid #fed7aa; background:#fff7ed; border-radius:8px; flex:1; }
    .quick-cash-display input:focus { outline:0; border-color:#f97316; background:#fff; }
    .quick-cash-grid { display:grid; grid-template-columns: repeat(4, 1fr); gap:8px; }
    .quick-cash-btn { padding:12px 6px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer; font-weight:600; font-size:13px; text-align:center; transition: all .12s; }
    .quick-cash-btn:hover { background:#fff7ed; border-color:#fdba74; }
    .quick-cash-btn:active { background:#fed7aa; }
    .quick-cash-btn.exact { background:#ecfdf5; border-color:#86efac; color:#166534; }
    .quick-cash-btn.exact:hover { background:#bbf7d0; }
    .quick-cash-btn.clear { background:#fef2f2; border-color:#fca5a5; color:#991b1b; }
    .quick-cash-btn.clear:hover { background:#fee2e2; }
    .change-box { margin-top:12px; padding:14px; border-radius:8px; background:#f0fdf4; border:1px solid #bbf7d0; display:none; }
    .change-box.show { display:block; }
    .change-box .label { font-size:13px; color:#15803d; }
    .change-box .amount { font-size:24px; font-weight:800; color:#166534; }
    @media (max-width: 600px) {
        .quick-cash-grid { grid-template-columns: repeat(3, 1fr); }
        .pay-type-grid { grid-template-columns: 1fr; }
    }
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

    <h2>Sale: {{ $sale->receipt_number }}</h2>

    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Disc</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
            <tr>
                <td>{{ $item->product->sku ?? '' }} - {{ $item->product->name ?? 'Deleted' }}</td>
                <td>{{ $item->qty }}</td>
                <td>Rp {{ number_format($item->base_selling_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="payment-summary">
        <div><strong>Subtotal</strong><span>Rp {{ number_format($sale->subtotal_amount, 0, ',', '.') }}</span></div>
        <div><strong>Discount</strong><span>Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span></div>
        <div><strong>Tax</strong><span>Rp {{ number_format($sale->tax_amount, 0, ',', '.') }} ({{ $sale->tax_percentage }}%)</span></div>
        <div><strong>Grand Total</strong><span style="font-size:18px;font-weight:800;color:#c2410c">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
    </div>

    <form method="post" action="{{ route('modules.pos.process-payment', $sale->id) }}" id="paymentForm">
        @csrf

        <input type="hidden" name="payment_method" id="paymentMethodHidden" value="cash">

        <div class="pay-type-grid">
            <div class="pay-type-card active" data-pay-type="cash" id="payTypeCash">
                <i class="fa-solid fa-money-bill-wave"></i>
                <span>Cash / Tunai</span>
            </div>
            <div class="pay-type-card" data-pay-type="transfer" id="payTypeTransfer">
                <i class="fa-solid fa-building-columns"></i>
                <span>Transfer / Bank</span>
            </div>
        </div>

        <div id="cashPaymentSection">
            <div class="quick-cash-box">
                <div class="quick-cash-display">
                    <input type="number" step="1000" min="0" name="amount_paid" id="amountPaid" value="{{ $sale->grand_total }}" readonly>
                </div>
                <div class="quick-cash-grid">
                    <button type="button" class="quick-cash-btn" data-amount="10000">Rp 10.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="20000">Rp 20.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="50000">Rp 50.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="100000">Rp 100.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="150000">Rp 150.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="200000">Rp 200.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="500000">Rp 500.000</button>
                    <button type="button" class="quick-cash-btn" data-amount="1000000">Rp 1.000.000</button>
                    <button type="button" class="quick-cash-btn exact" data-amount="exact">Uang Pas</button>
                    <button type="button" class="quick-cash-btn" data-amount="+10000">+ 10K</button>
                    <button type="button" class="quick-cash-btn" data-amount="+50000">+ 50K</button>
                    <button type="button" class="quick-cash-btn clear" data-amount="clear">Clear</button>
                </div>
                <div class="change-box" id="changeBox">
                    <div class="label">Kembalian</div>
                    <div class="amount" id="changeAmount">Rp 0</div>
                </div>
            </div>
        </div>

        <div id="transferPaymentSection" style="display:none">
            <div class="form-grid">
                <label class="full">Transfer Amount
                    <input type="number" step="1000" min="0" name="amount_paid_transfer" id="amountPaidTransfer" value="{{ $sale->grand_total }}" style="font-size:18px;font-weight:700">
                </label>
            </div>
            <div class="change-box" id="changeBoxTransfer">
                <div class="label">Kembalian / Kelebihan</div>
                <div class="amount" id="changeAmountTransfer">Rp 0</div>
            </div>
        </div>

        <div id="debtSection" style="display:none;margin-top:12px">
            <div class="form-grid">
                <label id="customerLabel">Customer (wajib untuk hutang)
                    <input type="hidden" name="customer_id" id="customerId">
                    <input type="text" class="lookup-input" id="customerLookupInput" value="" placeholder="Klik untuk pilih customer" readonly data-lookup-open>
                </label>
                <label id="dueDateLabel">Jatuh Tempo
                    <input type="date" name="debt_due_date" id="debtDueDate" value="{{ now()->addDays(30)->toDateString() }}">
                </label>
            </div>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="btn secondary" data-pay-mode="full" id="modeFull">Bayar Penuh</button>
            <button type="button" class="btn secondary" data-pay-mode="partial" id="modePartial">Bayar Sebagian</button>
            <button type="button" class="btn secondary" data-pay-mode="debt" id="modeDebt">Hutang</button>
        </div>

        <div class="row-actions" style="margin-top:16px;display:flex;gap:10px">
            <button type="submit" class="btn" id="processPayBtn" style="font-size:16px;padding:12px 28px">Process Payment</button>
            <a href="{{ route('modules.pos.open-cashier') }}" class="btn secondary">Save as Draft & Back</a>
        </div>
    </form>
</section>

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
        </div>
    </div>
</div>

<script>
(function () {
    const grandTotal = {{ $sale->grand_total }};
    const amountPaid = document.getElementById('amountPaid');
    const amountPaidTransfer = document.getElementById('amountPaidTransfer');
    const paymentMethodHidden = document.getElementById('paymentMethodHidden');
    const cashSection = document.getElementById('cashPaymentSection');
    const transferSection = document.getElementById('transferPaymentSection');
    const debtSection = document.getElementById('debtSection');
    const changeBox = document.getElementById('changeBox');
    const changeAmount = document.getElementById('changeAmount');
    const changeBoxTransfer = document.getElementById('changeBoxTransfer');
    const changeAmountTransfer = document.getElementById('changeAmountTransfer');
    const processPayBtn = document.getElementById('processPayBtn');

    let payMode = 'full';
    let payType = 'cash';

    function formatRupiah(val) {
        return 'Rp ' + (val || 0).toLocaleString('id-ID');
    }

    function updateChange() {
        const paid = parseInt(amountPaid.value || 0);
        const change = paid - grandTotal;
        if (change > 0) {
            changeBox.classList.add('show');
            changeAmount.textContent = formatRupiah(change);
        } else if (change === 0) {
            changeBox.classList.add('show');
            changeAmount.textContent = 'Rp 0 (Uang Pas)';
        } else {
            changeBox.classList.remove('show');
        }
    }

    function updateTransferChange() {
        const paid = parseInt(amountPaidTransfer.value || 0);
        const change = paid - grandTotal;
        if (change >= 0) {
            changeBoxTransfer.classList.add('show');
            changeAmountTransfer.textContent = formatRupiah(change);
        } else {
            changeBoxTransfer.classList.remove('show');
        }
    }

    document.querySelectorAll('.pay-type-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.pay-type-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            payType = card.dataset.payType;
            if (payType === 'cash') {
                cashSection.style.display = 'block';
                transferSection.style.display = 'none';
                updateChange();
            } else {
                cashSection.style.display = 'none';
                transferSection.style.display = 'block';
                updateTransferChange();
            }
        });
    });

    const modeButtons = document.querySelectorAll('[data-pay-mode]');
    function setPayMode(mode) {
        payMode = mode;
        modeButtons.forEach(b => {
            if (b.dataset.payMode === mode) {
                b.classList.remove('secondary');
                b.style.background = '#f97316';
                b.style.color = '#fff';
            } else {
                b.classList.add('secondary');
                b.style.background = '';
                b.style.color = '';
            }
        });

        if (mode === 'full') {
            paymentMethodHidden.value = 'cash';
            debtSection.style.display = 'none';
            document.getElementById('payTypeCash').style.display = '';
            document.getElementById('payTypeTransfer').style.display = '';
            if (payType === 'cash') {
                amountPaid.value = grandTotal;
                updateChange();
            } else {
                amountPaidTransfer.value = grandTotal;
                updateTransferChange();
            }
        } else if (mode === 'partial') {
            paymentMethodHidden.value = 'partial';
            debtSection.style.display = 'block';
            document.getElementById('payTypeCash').style.display = '';
            document.getElementById('payTypeTransfer').style.display = '';
            if (payType === 'cash') {
                amountPaid.value = Math.floor(grandTotal / 2);
                updateChange();
            } else {
                amountPaidTransfer.value = Math.floor(grandTotal / 2);
                updateTransferChange();
            }
        } else if (mode === 'debt') {
            paymentMethodHidden.value = 'debt';
            debtSection.style.display = 'block';
            cashSection.style.display = 'none';
            transferSection.style.display = 'none';
            document.querySelectorAll('.pay-type-card').forEach(c => c.classList.remove('active'));
        }
    }
    modeButtons.forEach(btn => btn.addEventListener('click', () => setPayMode(btn.dataset.payMode)));
    setPayMode('full');

    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.amount;
            let current = parseInt(amountPaid.value || 0);

            if (val === 'exact') {
                amountPaid.value = grandTotal;
            } else if (val === 'clear') {
                amountPaid.value = 0;
            } else if (val.startsWith('+')) {
                amountPaid.value = current + parseInt(val.substring(1));
            } else {
                amountPaid.value = parseInt(val);
            }
            updateChange();
        });
    });

    amountPaidTransfer.addEventListener('input', updateTransferChange);

    processPayBtn.addEventListener('click', (e) => {
        if (payMode === 'debt') {
            if (!document.getElementById('customerId').value) {
                e.preventDefault();
                Swal.fire({ title: 'Customer Wajib', text: 'Pilih customer untuk pembayaran hutang.', icon: 'warning', confirmButtonColor: '#f97316' });
                return false;
            }
        }
        if (payType === 'transfer' && (payMode === 'full' || payMode === 'partial')) {
            amountPaid.value = amountPaidTransfer.value;
        }
    });

    updateChange();

    const customerId = document.getElementById('customerId');
    const customerLookupInput = document.getElementById('customerLookupInput');
    const modal = document.getElementById('customerModal');
    const lookupRows = document.getElementById('customerLookupRows');
    const lookupSearch = document.getElementById('customerLookupSearch');
    const lookupPage = document.getElementById('customerLookupPage');
    const lookupPrev = document.getElementById('customerLookupPrev');
    const lookupNext = document.getElementById('customerLookupNext');
    let page = 1;
    let lastPage = 1;
    let debounce;

    function openCustomerLookup() {
        page = 1;
        lookupSearch.value = '';
        modal.classList.add('open');
        loadCustomers();
        setTimeout(() => lookupSearch.focus(), 50);
    }

    function closeCustomerLookup() {
        modal.classList.remove('open');
    }

    async function loadCustomers() {
        lookupRows.innerHTML = '<tr><td colspan="2" class="muted">Loading customers...</td></tr>';
        const params = new URLSearchParams({ page, q: lookupSearch.value });
        const response = await fetch(`{{ route('modules.pos.lookup-customers') }}?${params.toString()}`);
        const payload = await response.json();
        lastPage = payload.last_page || 1;
        lookupPage.textContent = `Page ${payload.current_page || 1} / ${lastPage}`;
        lookupPrev.disabled = (payload.current_page || 1) <= 1;
        lookupNext.disabled = (payload.current_page || 1) >= lastPage;

        if (!payload.data || payload.data.length === 0) {
            lookupRows.innerHTML = '<tr><td colspan="2" class="muted">No customers found.</td></tr>';
            return;
        }

        lookupRows.innerHTML = payload.data.map(item => `
            <tr data-id="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}" data-debt="${escapeHtml(String(item.debt || 0))}">
                <td>${escapeHtml(item.label)}</td>
                <td class="muted">${escapeHtml(item.description || '')}</td>
            </tr>
        `).join('');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({ '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#039;' }[char]));
    }

    function chooseCustomer(selected) {
        customerId.value = selected.dataset.id;
        customerLookupInput.value = selected.dataset.label;
        closeCustomerLookup();
    }

    customerLookupInput.addEventListener('click', openCustomerLookup);
    document.getElementById('customerLookupClose').addEventListener('click', closeCustomerLookup);
    modal.addEventListener('click', event => { if (event.target === modal) closeCustomerLookup(); });
    lookupRows.addEventListener('click', event => {
        const row = event.target.closest('tr[data-id]');
        if (row) chooseCustomer(row);
    });
    lookupPrev.addEventListener('click', () => { if (page > 1) { page--; loadCustomers(); } });
    lookupNext.addEventListener('click', () => { if (page < lastPage) { page++; loadCustomers(); } });
    lookupSearch.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => { page = 1; loadCustomers(); }, 300);
    });
})();
</script>
@endsection
