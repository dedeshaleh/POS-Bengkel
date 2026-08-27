@extends('layouts.app')

@section('title', 'Add Supplier Payable')
@section('subtitle', 'Create a payable from a purchase or manually.')

@section('content')
<section class="panel" style="max-width:720px">
    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    <form method="post" action="{{ route('supplier-payables.store') }}" id="payableForm">
        @csrf
        <div class="form-grid">
            <label class="full">Purchase (optional)
                <select name="purchase_id" id="purchaseId">
                    <option value="">-- No purchase --</option>
                    @foreach ($purchases as $purchase)
                        <option value="{{ $purchase->id }}" data-supplier-id="{{ $purchase->supplier_id }}" data-total="{{ $purchase->grand_total ?: $purchase->total_amount }}" {{ old('purchase_id') == $purchase->id ? 'selected' : '' }}>
                            {{ $purchase->invoice_number }} — {{ $purchase->supplier?->company_name ?? 'No supplier' }} — Rp {{ number_format($purchase->grand_total ?: $purchase->total_amount, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Supplier
                <select name="supplier_id" id="supplierId" required>
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Total Amount
                <input type="number" step="0.01" min="0" name="total_amount" id="totalAmount" value="{{ old('total_amount', 0) }}" required>
            </label>

            <label>Due Date
                <input type="date" name="due_date" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" required>
            </label>

            <label class="full">Notes
                <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
            </label>
        </div>

        <div class="row-actions" style="margin-top:16px">
            <button class="btn">Save Payable</button>
            <a class="btn secondary" href="{{ route('supplier-payables.index') }}">Back</a>
        </div>
    </form>
</section>

<script>
    (function () {
        const purchaseId = document.getElementById('purchaseId');
        const supplierId = document.getElementById('supplierId');
        const totalAmount = document.getElementById('totalAmount');

        function updateFromPurchase() {
            const option = purchaseId.options[purchaseId.selectedIndex];
            if (option.value) {
                supplierId.value = option.dataset.supplierId || '';
                totalAmount.value = option.dataset.total || 0;
                supplierId.disabled = true;
                totalAmount.readOnly = true;
            } else {
                supplierId.disabled = false;
                totalAmount.readOnly = false;
            }
        }

        purchaseId.addEventListener('change', updateFromPurchase);
        updateFromPurchase();
    })();
</script>
@endsection
