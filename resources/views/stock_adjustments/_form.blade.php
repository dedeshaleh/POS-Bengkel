<div class="stock-adjustment-form">
    <style>
        .stock-adjustment-form .lookup-field { width: 100%; }
        .stock-adjustment-form .lookup-display { min-width: 280px; width: 100%; }
        .stock-adjustment-form .batch-display { min-width: 220px; }
        .stock-adjustment-form .table th,
        .stock-adjustment-form .table td { white-space: nowrap; }
        .stock-adjustment-form .table td { min-width: 120px; }
        .stock-adjustment-form .scan-box { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .stock-adjustment-form .scan-box input { flex: 1; min-width: 240px; padding: 10px 12px; }
    </style>

    <div class="form-grid">
        <label>
            Warehouse
            <select name="warehouse_id" id="warehouseId" required>
                <option value="">Select warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $stockAdjustment->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Adjustment Date
            <input type="date" name="adjustment_date" value="{{ old('adjustment_date', isset($stockAdjustment) && $stockAdjustment->adjustment_date ? $stockAdjustment->adjustment_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        </label>

        <label>
            Reason
            <input type="text" name="reason" value="{{ old('reason', $stockAdjustment->reason ?? '') }}" maxlength="100" required>
        </label>

        <label class="full">
            Notes
            <textarea name="notes" rows="3">{{ old('notes', $stockAdjustment->notes ?? '') }}</textarea>
        </label>
    </div>

    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
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

    <div class="scan-box" style="margin-bottom:12px">
        <label style="font-weight:650">Scan Barcode / QR:</label>
        <input type="text" id="barcodeScan" placeholder="Scan barcode/QR atau ketik SKU lalu tekan Enter..." autocomplete="off">
        <span class="muted">Barcode scanner akan otomatis menambah baris.</span>
    </div>

    <table class="table" id="items-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch (optional)</th>
                <th>Expected Qty</th>
                <th>Actual Qty</th>
                <th>Notes</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = old('items', $stockAdjustment->items ?? []);
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
                                <input type="text" class="lookup-display batch-display" readonly placeholder="No specific batch" value="{{ $batchId ? $batchLabel($batchId) : '' }}">
                                <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                            </div>
                        </td>
                        <td><input type="number" name="items[{{ $index }}][expected_qty]" min="0" value="{{ $item['expected_qty'] ?? $item->expected_qty ?? 0 }}" required class="expected-qty"></td>
                        <td><input type="number" name="items[{{ $index }}][actual_qty]" min="0" value="{{ $item['actual_qty'] ?? $item->actual_qty ?? 0 }}" required class="actual-qty"></td>
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
                            <input type="text" class="lookup-display batch-display" readonly placeholder="No specific batch">
                            <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                        </div>
                    </td>
                    <td><input type="number" name="items[0][expected_qty]" min="0" value="0" required class="expected-qty"></td>
                    <td><input type="number" name="items[0][actual_qty]" min="0" value="0" required class="actual-qty"></td>
                    <td><input type="text" name="items[0][notes]"></td>
                    <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="display:flex;justify-content:flex-start;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0">
        <button type="button" class="btn" id="add-item">Add Item</button>
    </div>

    @include('partials.lookup-modal')

    <script>
        const productData = @json($productOptions);
        const batchData = @json($batchOptions);
        const productStockById = {};
        productData.forEach(function (p) {
            productStockById[p.id] = parseInt(p.info.replace('Stock: ', '')) || 0;
        });
        const batchQtyById = {};
        batchData.forEach(function (b) {
            batchQtyById[b.id] = b.qty;
        });

        function updateExpectedQty(row) {
            const productId = row.querySelector('.product-id').value;
            const batchId = row.querySelector('.batch-id').value;
            const expectedInput = row.querySelector('.expected-qty');
            if (expectedInput.dataset.userEdited === 'true') {
                return;
            }
            let expected = 0;
            if (batchId) {
                expected = batchQtyById[batchId] || 0;
            } else if (productId) {
                expected = productStockById[productId] || 0;
            }
            expectedInput.value = expected;
        }

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

        function buildRow(index, productId, productLabel) {
            const expected = productId ? (productStockById[productId] || 0) : 0;
            return `
                <tr class="item-row">
                    <td>
                        <div class="lookup-field">
                            <input type="hidden" name="items[${index}][product_id]" class="product-id" value="${productId ? productId : ''}">
                            <input type="text" class="lookup-display product-display" readonly placeholder="Click to select product" value="${productLabel ? productLabel.replace(/"/g, '&quot;') : ''}">
                            <button type="button" class="btn secondary product-lookup-btn">Search</button>
                        </div>
                    </td>
                    <td>
                        <div class="lookup-field">
                            <input type="hidden" name="items[${index}][inventory_batch_id]" class="batch-id">
                            <input type="text" class="lookup-display batch-display" readonly placeholder="No specific batch">
                            <button type="button" class="btn secondary batch-lookup-btn">Search</button>
                        </div>
                    </td>
                    <td><input type="number" name="items[${index}][expected_qty]" min="0" value="${expected}" required class="expected-qty"></td>
                    <td><input type="number" name="items[${index}][actual_qty]" min="0" value="0" required class="actual-qty"></td>
                    <td><input type="text" name="items[${index}][notes]"></td>
                    <td><button type="button" class="btn secondary" onclick="removeItem(this)" style="background:#b42318">Remove</button></td>
                </tr>
            `;
        }

        function addItemRow(productId, productLabel) {
            const tbody = document.querySelector('#items-table tbody');
            const index = tbody.querySelectorAll('.item-row').length;
            const temp = document.createElement('tbody');
            temp.innerHTML = buildRow(index, productId, productLabel);
            const newRow = temp.firstElementChild;
            tbody.appendChild(newRow);
            const actualInput = newRow.querySelector('.actual-qty');
            if (actualInput) actualInput.focus();
        }

        document.getElementById('add-item').addEventListener('click', function () {
            addItemRow(null, '');
        });

        const barcodeScan = document.getElementById('barcodeScan');
        barcodeScan.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const code = barcodeScan.value.trim();
            if (!code) return;

            const product = productData.find(function (p) {
                return (p.barcode && p.barcode.toLowerCase() === code.toLowerCase())
                    || (p.sku && p.sku.toLowerCase() === code.toLowerCase());
            });

            if (!product) {
                Swal.fire({ icon: 'error', title: 'Produk tidak ditemukan', text: 'Produk dengan barcode/SKU "' + code + '" tidak ditemukan.' });
                return;
            }

            addItemRow(product.id, product.label);
            barcodeScan.value = '';
            barcodeScan.focus();
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
                        updateExpectedQty(row);
                    }
                });
                return;
            }

            const batchBtn = e.target.closest('.batch-lookup-btn');
            if (batchBtn) {
                const row = batchBtn.closest('.item-row');
                const productId = row.querySelector('.product-id').value;
                const warehouseId = document.getElementById('warehouseId').value;
                if (!productId) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih produk terlebih dahulu.' });
                    return;
                }
                const hidden = row.querySelector('.batch-id');
                const display = row.querySelector('.batch-display');
                const filtered = batchData.filter(function (b) {
                    return b.product_id == productId && (!warehouseId || b.warehouse_id == warehouseId);
                });
                if (!filtered.length) {
                    Swal.fire({ icon: 'info', title: 'Batch tidak ditemukan', text: 'Tidak ada batch untuk produk ini di warehouse yang dipilih.' });
                    return;
                }
                openLookupModal({
                    title: 'Select Batch',
                    data: filtered,
                    onSelect: function (id, label) {
                        hidden.value = id;
                        display.value = label;
                        updateExpectedQty(row);
                    }
                });
            }
        });

        document.querySelector('#items-table').addEventListener('input', function (e) {
            if (e.target.classList.contains('expected-qty')) {
                e.target.dataset.userEdited = 'true';
            }
        });
    </script>
</div>
