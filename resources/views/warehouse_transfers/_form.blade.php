<div class="form-grid">
    <label>
        From Warehouse
        <select name="from_warehouse_id" id="from-warehouse" required>
            <option value="">Select source warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id', $warehouseTransfer->from_warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        To Warehouse
        <select name="to_warehouse_id" required>
            <option value="">Select destination warehouse</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id', $warehouseTransfer->to_warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Transfer Date
        <input type="date" name="transfer_date" value="{{ old('transfer_date', isset($warehouseTransfer) && $warehouseTransfer->transfer_date ? $warehouseTransfer->transfer_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </label>

    <label class="full">
        Notes
        <textarea name="notes" rows="3">{{ old('notes', $warehouseTransfer->notes ?? '') }}</textarea>
    </label>
</div>

@php
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'label' => $p->sku . ' - ' . $p->name,
        'info' => 'Stock: ' . $p->total_stock,
        'search' => $p->sku . ' ' . $p->name . ' ' . ($p->barcode ?? ''),
    ]);
    $batchOptions = $batches->map(fn ($b) => [
        'id' => $b->id,
        'label' => 'Batch #' . $b->id . ' - ' . $b->product->sku . ' (' . $b->current_qty . ')',
        'info' => $b->warehouse?->name ?? '',
        'search' => $b->product->sku . ' ' . $b->product->name . ' Batch ' . $b->id,
        'product_id' => $b->product_id,
        'warehouse_id' => $b->warehouse_id,
        'qty' => $b->current_qty,
    ]);

    $productLabel = function ($id) use ($products) {
        $p = $products->firstWhere('id', $id);
        return $p ? $p->sku . ' - ' . $p->name : '';
    };
    $batchLabel = function ($id) use ($batches) {
        $b = $batches->firstWhere('id', $id);
        return $b ? 'Batch #' . $b->id . ' - ' . $b->product->sku . ' (' . $b->current_qty . ')' : '';
    };
@endphp

<h3 style="margin:24px 0 12px">Items</h3>
<table class="table" id="items-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Batch (optional)</th>
            <th>Qty</th>
            <th>Notes</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @php
            $items = old('items', $warehouseTransfer->items ?? []);
        @endphp
        @if (count($items) > 0)
            @foreach ($items as $index => $item)
                @php
                    $productId = (int) ($item['product_id'] ?? $item->product_id ?? 0);
                    $batchId = (int) ($item['inventory_batch_id'] ?? $item->inventory_batch_id ?? 0);
                @endphp
                <tr class="item-row">
                    <td>
                        <div class="lookup-field">
                            <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $productId ? $productId : '' }}">
                            <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product" value="{{ $productId ? $productLabel($productId) : '' }}">
                            <button type="button" class="btn secondary product-lookup-btn">Search</button>
                        </div>
                    </td>
                    <td>
                        <div class="lookup-field">
                            <input type="hidden" name="items[{{ $index }}][inventory_batch_id]" class="batch-id" value="{{ $batchId ? $batchId : '' }}">
                            <input type="text" class="lookup-display batch-display" readonly placeholder="Auto (FIFO)" value="{{ $batchId ? $batchLabel($batchId) : '' }}">
                            <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                        </div>
                    </td>
                    <td><input type="number" name="items[{{ $index }}][qty]" min="1" value="{{ $item['qty'] ?? $item->qty ?? 1 }}" required class="qty"></td>
                    <td><input type="text" name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? $item->notes ?? '' }}"></td>
                    <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
                </tr>
            @endforeach
        @else
            <tr class="item-row">
                <td>
                    <div class="lookup-field">
                        <input type="hidden" name="items[0][product_id]" class="product-id">
                        <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product">
                        <button type="button" class="btn secondary product-lookup-btn">Search</button>
                    </div>
                </td>
                <td>
                    <div class="lookup-field">
                        <input type="hidden" name="items[0][inventory_batch_id]" class="batch-id">
                        <input type="text" class="lookup-display batch-display" readonly placeholder="Auto (FIFO)">
                        <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                    </div>
                </td>
                <td><input type="number" name="items[0][qty]" min="1" value="1" required class="qty"></td>
                <td><input type="text" name="items[0][notes]"></td>
                <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
            </tr>
        @endif
    </tbody>
</table>

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0">
    <button type="button" class="btn" id="add-item">Add Item</button>
</div>

@include('partials.lookup-modal')

<script>
    const productData = @json($productOptions);
    const batchData = @json($batchOptions);

    function removeItem(button) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            button.closest('.item-row').remove();
            reindex();
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
                    <div class="lookup-field">
                        <input type="hidden" name="items[${index}][product_id]" class="product-id">
                        <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product">
                        <button type="button" class="btn secondary product-lookup-btn">Search</button>
                    </div>
                </td>
                <td>
                    <div class="lookup-field">
                        <input type="hidden" name="items[${index}][inventory_batch_id]" class="batch-id">
                        <input type="text" class="lookup-display batch-display" readonly placeholder="Auto (FIFO)">
                        <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                    </div>
                </td>
                <td><input type="number" name="items[${index}][qty]" min="1" value="1" required class="qty"></td>
                <td><input type="text" name="items[${index}][notes]"></td>
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
                    row.querySelector('.batch-id').value = '';
                    row.querySelector('.batch-display').value = '';
                }
            });
            return;
        }

        const batchBtn = e.target.closest('.batch-lookup-btn');
        if (batchBtn) {
            const row = batchBtn.closest('.item-row');
            const productId = row.querySelector('.product-id').value;
            const warehouseId = document.getElementById('from-warehouse').value;
            if (!productId) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih produk terlebih dahulu.' });
                return;
            }
            const hidden = row.querySelector('.batch-id');
            const display = row.querySelector('.batch-display');
            const filtered = batchData.filter(function (b) {
                return b.product_id == productId && b.warehouse_id == warehouseId;
            });
            if (!filtered.length) {
                Swal.fire({ icon: 'info', title: 'Batch tidak ditemukan', text: 'Tidak ada batch untuk produk ini di warehouse asal.' });
                return;
            }
            openLookupModal({
                title: 'Select Batch',
                data: filtered,
                onSelect: function (id, label) {
                    hidden.value = id;
                    display.value = label;
                }
            });
        }
    });
</script>
