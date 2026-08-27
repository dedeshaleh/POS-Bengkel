@php
    $editing = isset($voucher);
    $selectedProducts = $editing ? $voucher->products->pluck('id')->toArray() : [];
@endphp

<div class="form-grid">
    <label>Code <input name="code" value="{{ old('code', $voucher->code ?? '') }}" required style="text-transform:uppercase"></label>
    <label>Name <input name="name" value="{{ old('name', $voucher->name ?? '') }}" placeholder="e.g. Diskon Oli 10%"></label>

    <label>Discount Type
        <select name="discount_type" id="discountType" required>
            <option value="percentage" {{ old('discount_type', $voucher->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
            <option value="fixed" {{ old('discount_type', $voucher->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Nominal (Rp)</option>
        </select>
    </label>

    <label>Discount Value <input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $voucher->discount_value ?? '') }}" required></label>

    <label id="maxDiscountLabel" style="display:none">Max Discount (Rp) <input type="number" step="0.01" min="0" name="max_discount_amount" value="{{ old('max_discount_amount', $voucher->max_discount_amount ?? '') }}" placeholder="Kosongkan jika tanpa batas"></label>

    <label>Scope
        <select name="scope_type" id="scopeType" required>
            <option value="transaction" {{ old('scope_type', $voucher->scope_type ?? 'transaction') === 'transaction' ? 'selected' : '' }}>Transaksi (potong total)</option>
            <option value="item" {{ old('scope_type', $voucher->scope_type ?? '') === 'item' ? 'selected' : '' }}>Item Tertentu</option>
        </select>
    </label>

    <label>Min Transaction (Rp) <input type="number" step="0.01" min="0" name="min_transaction_amount" value="{{ old('min_transaction_amount', $voucher->min_transaction_amount ?? 0) }}"></label>

    <label>Valid From <input type="date" name="valid_from" value="{{ old('valid_from', $voucher->valid_from?->format('Y-m-d') ?? '') }}"></label>
    <label>Valid Until <input type="date" name="valid_until" value="{{ old('valid_until', $voucher->valid_until?->format('Y-m-d') ?? '') }}"></label>

    <label>Usage Limit <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit ?? 1) }}" required></label>

    <label class="full"><span><input type="checkbox" name="is_active" value="1" style="width:auto" {{ old('is_active', $voucher->is_active ?? true) ? 'checked' : '' }}> Active</span></label>
</div>

<div id="productSelection" style="display:none;margin-top:16px">
    <h3 style="margin-bottom:8px">Select Products for Item Scope</h3>
    <div style="max-height:300px;overflow-y:auto;border:1px solid var(--line);border-radius:8px;padding:10px">
        @foreach ($products as $product)
            <label style="display:flex;gap:8px;align-items:center;padding:4px 0;cursor:pointer">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" style="width:auto" {{ in_array($product->id, old('product_ids', $selectedProducts)) ? 'checked' : '' }}>
                <span><strong>{{ $product->sku }}</strong> — {{ $product->name }}</span>
            </label>
        @endforeach
    </div>
</div>

<script>
(function () {
    var dt = document.getElementById('discountType');
    var maxLabel = document.getElementById('maxDiscountLabel');
    var scope = document.getElementById('scopeType');
    var prodSection = document.getElementById('productSelection');

    function toggleMax() {
        maxLabel.style.display = dt.value === 'percentage' ? '' : 'none';
    }

    function toggleProducts() {
        prodSection.style.display = scope.value === 'item' ? '' : 'none';
    }

    dt.addEventListener('change', toggleMax);
    scope.addEventListener('change', toggleProducts);
    toggleMax();
    toggleProducts();
})();
</script>
