<div class="form-grid">
    <label>
        Customer
        <select name="customer_id" required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" {{ old('customer_id', $serviceOrder->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }} {{ $customer->license_plate ? "({$customer->license_plate})" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Mechanic
        <select name="mechanic_id">
            <option value="">Select mechanic</option>
            @foreach ($mechanics as $mechanic)
                <option value="{{ $mechanic->id }}" {{ old('mechanic_id', $serviceOrder->mechanic_id ?? '') == $mechanic->id ? 'selected' : '' }}>
                    {{ $mechanic->name }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Status
        <select name="status" required>
            @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                <option value="{{ $value }}" {{ old('status', $serviceOrder->status ?? 'pending') == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Estimated Completion
        <input type="date" name="estimated_completion" value="{{ old('estimated_completion', isset($serviceOrder) && $serviceOrder->estimated_completion ? $serviceOrder->estimated_completion->format('Y-m-d') : '') }}">
    </label>

    <label>
        Labor Cost (Biaya Jasa)
        <input type="number" name="labor_cost" min="0" step="1000" value="{{ old('labor_cost', $serviceOrder->labor_cost ?? 0) }}" placeholder="0">
    </label>

    <label>
        Other Cost
        <input type="number" name="other_cost" min="0" step="1000" value="{{ old('other_cost', $serviceOrder->other_cost ?? 0) }}" placeholder="0">
    </label>

    <label class="full">
        Notes
        <textarea name="notes" rows="3">{{ old('notes', $serviceOrder->notes ?? '') }}</textarea>
    </label>
</div>

@php
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'label' => $p->sku . ' - ' . $p->name,
        'info' => 'Stock: ' . $p->total_stock,
        'search' => $p->sku . ' ' . $p->name . ' ' . ($p->barcode ?? ''),
    ]);
    $productLabel = function ($id) use ($products) {
        $p = $products->firstWhere('id', $id);
        return $p ? $p->sku . ' - ' . $p->name : '';
    };
@endphp

<h3 style="margin:24px 0 12px">Items</h3>
<table class="table" id="items-table">
    <thead>
        <tr>
            <th>Type</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Sell Price</th>
            <th>Notes</th>
            <th>Subtotal</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @php
            $items = old('items', $serviceOrder->items ?? []);
        @endphp
        @if (count($items) > 0)
            @foreach ($items as $index => $item)
                @php
                    $productId = (int) ($item['product_id'] ?? $item->product_id ?? 0);
                    $itemType = $item['item_type'] ?? $item->item_type ?? 'sparepart';
                    $itemName = $item['item_name'] ?? $item->item_name ?? '';
                @endphp
                <tr class="item-row">
                    <td>
                        <select name="items[{{ $index }}][item_type]" class="item-type" onchange="toggleItemFields(this)">
                            <option value="sparepart" {{ $itemType === 'sparepart' ? 'selected' : '' }}>Sparepart</option>
                            <option value="service" {{ $itemType === 'service' ? 'selected' : '' }}>Jasa</option>
                            <option value="other" {{ $itemType === 'other' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </td>
                    <td>
                        <div class="sparepart-field" style="{{ $itemType === 'sparepart' ? '' : 'display:none' }}">
                            <div class="lookup-field">
                                <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $productId ? $productId : '' }}">
                                <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product" value="{{ $productId ? $productLabel($productId) : '' }}">
                                <button type="button" class="btn secondary product-lookup-btn">Search</button>
                            </div>
                        </div>
                        <div class="non-sparepart-field" style="{{ $itemType !== 'sparepart' ? '' : 'display:none' }}">
                            <input type="text" name="items[{{ $index }}][item_name]" class="item-name" placeholder="Nama jasa / item" value="{{ $itemName }}">
                        </div>
                        <input type="hidden" name="items[{{ $index }}][buy_price]" class="buy-price" value="{{ $item['buy_price'] ?? $item->buy_price ?? 0 }}">
                    </td>
                    <td><input type="number" name="items[{{ $index }}][qty]" min="1" value="{{ $item['qty'] ?? $item->qty ?? 1 }}" required class="qty"></td>
                    <td><input type="number" name="items[{{ $index }}][selling_price]" min="0" step="0.01" value="{{ $item['selling_price'] ?? $item->selling_price ?? 0 }}" required class="selling-price"></td>
                    <td><input type="text" name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? $item->notes ?? '' }}"></td>
                    <td class="subtotal">Rp 0</td>
                    <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
                </tr>
            @endforeach
        @else
            <tr class="item-row">
                <td>
                    <select name="items[0][item_type]" class="item-type" onchange="toggleItemFields(this)">
                        <option value="sparepart" selected>Sparepart</option>
                        <option value="service">Jasa</option>
                        <option value="other">Lainnya</option>
                    </select>
                </td>
                <td>
                    <div class="sparepart-field">
                        <div class="lookup-field">
                            <input type="hidden" name="items[0][product_id]" class="product-id">
                            <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product">
                            <button type="button" class="btn secondary product-lookup-btn">Search</button>
                        </div>
                    </div>
                    <div class="non-sparepart-field" style="display:none">
                        <input type="text" name="items[0][item_name]" class="item-name" placeholder="Nama jasa / item">
                    </div>
                    <input type="hidden" name="items[0][buy_price]" class="buy-price" value="0">
                </td>
                <td><input type="number" name="items[0][qty]" min="1" value="1" required class="qty"></td>
                <td><input type="number" name="items[0][selling_price]" min="0" step="0.01" value="0" required class="selling-price"></td>
                <td><input type="text" name="items[0][notes]"></td>
                <td class="subtotal">Rp 0</td>
                <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
            </tr>
        @endif
    </tbody>
</table>

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0">
    <button type="button" class="btn" id="add-item">Add Item</button>
    <div style="font-size:16px;font-weight:750">Total: <span id="grand-total">Rp 0</span></div>
</div>

@include('partials.lookup-modal')

<script>
    const productData = @json($productOptions);

    function toggleItemFields(selectEl) {
        const row = selectEl.closest('.item-row');
        const type = selectEl.value;
        const sparepartField = row.querySelector('.sparepart-field');
        const nonSparepartField = row.querySelector('.non-sparepart-field');
        if (type === 'sparepart') {
            sparepartField.style.display = '';
            nonSparepartField.style.display = 'none';
        } else {
            sparepartField.style.display = 'none';
            nonSparepartField.style.display = '';
            row.querySelector('.product-id').value = '';
            row.querySelector('.product-display').value = '';
        }
        recalculate();
    }

    function recalculate() {
        let itemsTotal = 0;
        document.querySelectorAll('.item-row').forEach(function (row) {
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.selling-price').value) || 0;
            const subtotal = qty * price;
            itemsTotal += subtotal;
            row.querySelector('.subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        });
        const laborCost = parseFloat(document.querySelector('input[name="labor_cost"]')?.value) || 0;
        const otherCost = parseFloat(document.querySelector('input[name="other_cost"]')?.value) || 0;
        const grandTotal = itemsTotal + laborCost + otherCost;
        document.getElementById('grand-total').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function removeItem(button) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            button.closest('.item-row').remove();
            reindex();
            recalculate();
        }
    }

    function reindex() {
        document.querySelectorAll('.item-row').forEach(function (row, index) {
            row.querySelectorAll('input, select, textarea').forEach(function (input) {
                const name = input.getAttribute('name');
                if (name && name.includes('items[')) {
                    input.setAttribute('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
                }
            });
        });
    }

    function buildEmptyRow(index) {
        return `
            <tr class="item-row">
                <td>
                    <select name="items[${index}][item_type]" class="item-type" onchange="toggleItemFields(this)">
                        <option value="sparepart" selected>Sparepart</option>
                        <option value="service">Jasa</option>
                        <option value="other">Lainnya</option>
                    </select>
                </td>
                <td>
                    <div class="sparepart-field">
                        <div class="lookup-field">
                            <input type="hidden" name="items[${index}][product_id]" class="product-id">
                            <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product">
                            <button type="button" class="btn secondary product-lookup-btn">Search</button>
                        </div>
                    </div>
                    <div class="non-sparepart-field" style="display:none">
                        <input type="text" name="items[${index}][item_name]" class="item-name" placeholder="Nama jasa / item">
                    </div>
                    <input type="hidden" name="items[${index}][buy_price]" class="buy-price" value="0">
                </td>
                <td><input type="number" name="items[${index}][qty]" min="1" value="1" required class="qty"></td>
                <td><input type="number" name="items[${index}][selling_price]" min="0" step="0.01" value="0" required class="selling-price"></td>
                <td><input type="text" name="items[${index}][notes]"></td>
                <td class="subtotal">Rp 0</td>
                <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
            </tr>
        `;
    }

    document.getElementById('add-item').addEventListener('click', function () {
        const tbody = document.querySelector('#items-table tbody');
        const index = tbody.querySelectorAll('.item-row').length;
        const temp = document.createElement('tbody');
        temp.innerHTML = buildEmptyRow(index);
        tbody.appendChild(temp.firstElementChild);
    });

    document.querySelector('#items-table').addEventListener('click', function (e) {
        const productBtn = e.target.closest('.product-lookup-btn');
        if (productBtn) {
            const row = productBtn.closest('.item-row');
            const hidden = row.querySelector('.product-id');
            const display = row.querySelector('.product-display');
            openLookupModal({
                title: 'Select Product',
                data: productData,
                onSelect: function (id, label) {
                    hidden.value = id;
                    display.value = label;
                    const p = productData.find(x => x.id == id);
                    if (p) {
                        row.querySelector('.buy-price').value = 0;
                    }
                    recalculate();
                }
            });
        }
    });

    document.querySelector('#items-table').addEventListener('input', recalculate);
    document.querySelector('input[name="labor_cost"]')?.addEventListener('input', recalculate);
    document.querySelector('input[name="other_cost"]')?.addEventListener('input', recalculate);
    recalculate();
</script>
