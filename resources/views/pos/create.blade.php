@extends('layouts.app')

@section('title', 'Point of Sale')
@section('subtitle', 'Create service sales and lock stock immediately with FIFO.')

@section('content')
<section class="panel">
    <form method="post" action="{{ route('pos.store') }}" class="grid">
        @csrf
        <div class="form-grid">
            <label class="full">Scan Barcode / QR <input id="scanCode" autocomplete="off" placeholder="Scan barcode or QR code here"></label>
            <label>Customer
                <select name="customer_id">
                    <option value="">Walk-in customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} {{ $customer->license_plate ? '(' . $customer->license_plate . ')' : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>Payment Status
                <select name="payment_status">
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </label>
            <label>Payment Method <input name="payment_method" value="cash"></label>
            <label>Amount Paid <input type="number" step="0.01" min="0" name="amount_paid" value="0"></label>
            <label>Discount <input type="number" step="0.01" min="0" name="discount_amount" value="0"></label>
            <label>Tax % <input type="number" step="0.01" min="0" name="tax_percentage" value="{{ $tax }}"></label>
        </div>

        <table class="table">
            <thead><tr><th style="width:45%">Product</th><th>Qty</th><th>Selling Price</th></tr></thead>
            <tbody>
                @for ($i = 0; $i < 4; $i++)
                    <tr>
                        <td>
                            <select name="product_id[]" {{ $i === 0 ? 'required' : '' }}>
                                <option value="">Select item</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-code="{{ $product->barcode ?: $product->sku }}" data-sku="{{ $product->sku }}">
                                        {{ $product->sku }} - {{ $product->name }}
                                        {{ $product->is_bundle ? '(Bundle)' : '(Stock: ' . $product->total_stock . ')' }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" min="1" name="qty[]" value="{{ $i === 0 ? 1 : '' }}"></td>
                        <td><input type="number" step="0.01" min="0" name="selling_price[]" value="{{ $i === 0 ? 0 : '' }}"></td>
                    </tr>
                @endfor
            </tbody>
        </table>
        <div><button class="btn">Save Sale</button></div>
    </form>
</section>
<script>
    (function () {
        const scan = document.getElementById('scanCode');
        const selects = Array.from(document.querySelectorAll('select[name="product_id[]"]'));
        const qtyInputs = Array.from(document.querySelectorAll('input[name="qty[]"]'));
        const optionByCode = new Map();

        selects[0]?.querySelectorAll('option[value]').forEach(option => {
            if (!option.value) return;
            optionByCode.set((option.dataset.code || '').toUpperCase(), option.value);
            optionByCode.set((option.dataset.sku || '').toUpperCase(), option.value);
        });

        scan?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const code = scan.value.trim().toUpperCase();
            if (!code || !optionByCode.has(code)) return;
            const productId = optionByCode.get(code);
            const index = selects.findIndex(select => !select.value || select.value === productId);
            const targetIndex = index >= 0 ? index : selects.length - 1;
            selects[targetIndex].value = productId;
            qtyInputs[targetIndex].value = qtyInputs[targetIndex].value ? Number(qtyInputs[targetIndex].value) + 1 : 1;
            scan.value = '';
        });
    })();
</script>
@endsection
