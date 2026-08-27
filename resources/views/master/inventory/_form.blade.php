@csrf
@php
    $selectedCategoryId = old('category_id', $product->category_id ?? '');
    $selectedCategoryName = old('category_label', $product->category?->name ?? '');
    $selectedItemType = old('item_type_code', $product->item_type_code ?? '');
    $selectedItemTypeName = $itemTypes->firstWhere('code', $selectedItemType)?->name ?? $selectedItemType;
    $selectedUom = old('base_uom_code', $product->base_uom_code ?? '');
    $selectedUomName = $uoms->firstWhere('code', $selectedUom)?->name ?? $selectedUom;
    $existingBundleItems = collect(old('bundle_component_ids'))
        ->map(function ($componentId, $index) {
            return [
                'id' => $componentId,
                'label' => old('bundle_component_labels.' . $index, ''),
                'qty' => old('bundle_component_qtys.' . $index, 1),
            ];
        })
        ->filter(fn ($item) => ! empty($item['id']))
        ->values();

    if ($existingBundleItems->isEmpty() && isset($product)) {
        $existingBundleItems = $product->bundleItems->map(fn ($item) => [
            'id' => $item->component_product_id,
            'label' => $item->component?->sku . ' - ' . $item->component?->name,
            'qty' => $item->qty,
        ])->values();
    }
@endphp
<style>
    .lookup-field { display: grid; gap: 6px; }
    .lookup-control { display: grid; grid-template-columns: minmax(0, 1fr); gap: 8px; }
    .lookup-input { cursor: pointer; background: #fff; }
    .lookup-input:focus { outline: 0; border-color: #fdba74; box-shadow: 0 0 0 3px rgba(249, 115, 22, .18); }
    .lookup-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, .45);
        padding: 18px;
    }
    .lookup-modal.open { display: flex; }
    .lookup-dialog {
        width: min(760px, 100%);
        max-height: min(720px, 92vh);
        overflow: hidden;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 22px 70px rgba(15, 23, 42, .28);
        display: grid;
        grid-template-rows: auto auto minmax(0, 1fr) auto;
    }
    .lookup-head, .lookup-foot { padding: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lookup-head { border-bottom: 1px solid #e2e8f0; }
    .lookup-foot { border-top: 1px solid #e2e8f0; }
    .lookup-body { overflow: auto; padding: 0 14px 14px; }
    .lookup-search { padding: 12px 14px; }
    .lookup-table tr { cursor: pointer; }
    .lookup-table tr:hover td { background: #fff7ed; }
    .bundle-panel { display: none; margin-top: 14px; }
    .bundle-panel.open { display: block; }
    .bundle-row { display: grid; grid-template-columns: minmax(220px, 1fr) 100px auto; gap: 8px; align-items: center; margin-bottom: 8px; }
    @media (max-width: 700px) {
        .bundle-row { grid-template-columns: 1fr; }
    }
</style>

<div class="form-grid">
    <label>SKU <input value="{{ $product->sku ?? 'Auto generated after save' }}" readonly></label>
    <label>Name <input name="name" value="{{ old('name', $product->name ?? '') }}" required></label>
    <label>Barcode / QR Value <input name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="Empty = use generated SKU"></label>

    <div class="lookup-field">
        <label>Category</label>
        <div class="lookup-control">
            <input type="hidden" name="category_id" id="category_id" value="{{ $selectedCategoryId }}">
            <input type="hidden" name="category_label" id="category_label" value="{{ $selectedCategoryName }}">
            <input class="lookup-input" id="category_display" value="{{ $selectedCategoryName ?: 'No category selected' }}" readonly data-lookup-open
                data-title="Select Category"
                data-url="{{ route('master.inventory.lookup.categories') }}"
                data-target-value="category_id"
                data-target-label="category_display"
                data-target-extra-label="category_label">
        </div>
    </div>

    <div class="lookup-field">
        <label>Item Type</label>
        <div class="lookup-control">
            <input type="hidden" name="item_type_code" id="item_type_code" value="{{ $selectedItemType }}" required>
            <input class="lookup-input" id="item_type_display" value="{{ $selectedItemTypeName ?: 'Select item type' }}" readonly required data-lookup-open
                data-title="Select Item Type"
                data-url="{{ route('master.inventory.lookup.masters', 'ITEM_TYPE') }}"
                data-target-value="item_type_code"
                data-target-label="item_type_display">
        </div>
    </div>

    <div class="lookup-field">
        <label>Base UOM</label>
        <div class="lookup-control">
            <input type="hidden" name="base_uom_code" id="base_uom_code" value="{{ $selectedUom }}" required>
            <input class="lookup-input" id="base_uom_display" value="{{ $selectedUomName ?: 'Select base UOM' }}" readonly required data-lookup-open
                data-title="Select Base UOM"
                data-url="{{ route('master.inventory.lookup.masters', 'UOM') }}"
                data-target-value="base_uom_code"
                data-target-label="base_uom_display">
        </div>
    </div>

    <label>Markup Type
        <select name="markup_type" required>
            <option value="percentage" {{ old('markup_type', $product->markup_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
            <option value="fixed" {{ old('markup_type', $product->markup_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed</option>
        </select>
    </label>
    <label>Markup Value <input type="number" step="0.01" min="0" name="markup_value" value="{{ old('markup_value', $product->markup_value ?? 0) }}" required></label>
    <label>Minimum Stock <input type="number" min="0" name="min_stock_level" value="{{ old('min_stock_level', $product->min_stock_level ?? 5) }}" required></label>
    <label class="full"><span><input id="isBundleToggle" type="checkbox" name="is_bundle" value="1" style="width:auto" {{ old('is_bundle', $product->is_bundle ?? false) ? 'checked' : '' }}> Bundle / virtual product</span></label>
</div>

<section class="panel bundle-panel {{ old('is_bundle', $product->is_bundle ?? false) ? 'open' : '' }}" id="bundlePanel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
        <h2>Bundle Components</h2>
        <button type="button" class="btn secondary" data-no-loading id="addBundleRow">Add Component</button>
    </div>
    <div id="bundleRows">
        @foreach ($existingBundleItems as $item)
            <div class="bundle-row">
                <input type="hidden" name="bundle_component_ids[]" value="{{ $item['id'] }}" class="bundle-component-id">
                <input type="hidden" name="bundle_component_labels[]" value="{{ $item['label'] }}" class="bundle-component-label-hidden">
                <input class="lookup-input bundle-component-label" value="{{ $item['label'] }}" readonly data-lookup-open
                    data-title="Select Bundle Component"
                    data-url="{{ route('master.inventory.lookup.components', ['exclude_id' => $product->id ?? 0]) }}"
                    data-target-value=""
                    data-target-label="">
                <input type="number" name="bundle_component_qtys[]" value="{{ $item['qty'] }}" min="1" required>
                <button type="button" class="btn" style="background:#b42318" data-no-loading data-remove-bundle-row>Remove</button>
            </div>
        @endforeach
    </div>
</section>

<div class="lookup-modal" id="lookupModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 id="lookupTitle" style="margin:0">Select Data</h2>
            <button type="button" class="btn secondary" data-no-loading id="lookupClose">Close</button>
        </div>
        <div class="lookup-search">
            <input id="lookupSearch" placeholder="Search..." autocomplete="off">
        </div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead><tr><th>Name</th><th>Code / Info</th></tr></thead>
                <tbody id="lookupRows">
                    <tr><td colspan="2" class="muted">No data loaded.</td></tr>
                </tbody>
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
        const modal = document.getElementById('lookupModal');
        if (!modal || modal.dataset.ready) return;
        modal.dataset.ready = 'true';

        const title = document.getElementById('lookupTitle');
        const search = document.getElementById('lookupSearch');
        const rows = document.getElementById('lookupRows');
        const pageLabel = document.getElementById('lookupPage');
        const closeBtn = document.getElementById('lookupClose');
        const prevBtn = document.getElementById('lookupPrev');
        const nextBtn = document.getElementById('lookupNext');
        let state = { url: '', page: 1, lastPage: 1, targetValue: '', targetLabel: '', targetExtraLabel: '' };
        let activeBundleRow = null;
        let debounce;

        function openLookup(button) {
            state = {
                url: button.dataset.url,
                page: 1,
                lastPage: 1,
                targetValue: button.dataset.targetValue,
                targetLabel: button.dataset.targetLabel,
                targetExtraLabel: button.dataset.targetExtraLabel || '',
            };
            activeBundleRow = button.closest('.bundle-row');
            title.textContent = button.dataset.title || 'Select Data';
            search.value = '';
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            loadRows();
            setTimeout(() => search.focus(), 50);
        }

        function closeLookup() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

        async function loadRows() {
            rows.innerHTML = '<tr><td colspan="2" class="muted">Loading data...</td></tr>';
            const params = new URLSearchParams({ page: state.page, q: search.value });
            const response = await fetch(`${state.url}?${params.toString()}`);
            const payload = await response.json();
            state.lastPage = payload.last_page || 1;
            pageLabel.textContent = `Page ${payload.current_page || 1} / ${state.lastPage}`;
            prevBtn.disabled = (payload.current_page || 1) <= 1;
            nextBtn.disabled = (payload.current_page || 1) >= state.lastPage;

            if (!payload.data || payload.data.length === 0) {
                rows.innerHTML = '<tr><td colspan="2" class="muted">No data found.</td></tr>';
                return;
            }

            rows.innerHTML = payload.data.map(item => `
                <tr data-value="${escapeHtml(String(item.value))}" data-label="${escapeHtml(item.label)}">
                    <td>${escapeHtml(item.label)}</td>
                    <td class="muted">${escapeHtml(item.description || '')}</td>
                </tr>
            `).join('');
        }

        function escapeHtml(value) {
            return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
        }

        document.addEventListener('click', function (event) {
            const opener = event.target.closest('[data-lookup-open]');
            if (opener) {
                openLookup(opener);
                return;
            }

            const row = event.target.closest('#lookupRows tr[data-value]');
            if (!row) return;
            const valueEl = document.getElementById(state.targetValue);
            const labelEl = document.getElementById(state.targetLabel);
            const extraLabelEl = state.targetExtraLabel ? document.getElementById(state.targetExtraLabel) : null;
            if (activeBundleRow) {
                activeBundleRow.querySelector('.bundle-component-id').value = row.dataset.value;
                activeBundleRow.querySelector('.bundle-component-label').value = row.dataset.label;
                activeBundleRow.querySelector('.bundle-component-label-hidden').value = row.dataset.label;
            } else {
                if (valueEl) valueEl.value = row.dataset.value;
                if (labelEl) labelEl.value = row.dataset.label;
                if (extraLabelEl) extraLabelEl.value = row.dataset.label;
            }
            closeLookup();
        });

        closeBtn.addEventListener('click', closeLookup);
        modal.addEventListener('click', event => { if (event.target === modal) closeLookup(); });
        prevBtn.addEventListener('click', () => { if (state.page > 1) { state.page--; loadRows(); } });
        nextBtn.addEventListener('click', () => { if (state.page < state.lastPage) { state.page++; loadRows(); } });
        search.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.page = 1;
                loadRows();
            }, 300);
        });

        const bundleToggle = document.getElementById('isBundleToggle');
        const bundlePanel = document.getElementById('bundlePanel');
        const bundleRows = document.getElementById('bundleRows');
        const addBundleRow = document.getElementById('addBundleRow');
        const componentLookupUrl = @json(route('master.inventory.lookup.components', ['exclude_id' => $product->id ?? 0]));

        function toggleBundlePanel() {
            if (!bundlePanel || !bundleToggle) return;
            bundlePanel.classList.toggle('open', bundleToggle.checked);
        }

        function makeBundleRow() {
            const row = document.createElement('div');
            row.className = 'bundle-row';
            row.innerHTML = `
                <input type="hidden" name="bundle_component_ids[]" class="bundle-component-id">
                <input type="hidden" name="bundle_component_labels[]" class="bundle-component-label-hidden">
                <input class="lookup-input bundle-component-label" value="Click to select component" readonly data-lookup-open
                    data-title="Select Bundle Component"
                    data-url="${componentLookupUrl}"
                    data-target-value=""
                    data-target-label="">
                <input type="number" name="bundle_component_qtys[]" value="1" min="1" required>
                <button type="button" class="btn" style="background:#b42318" data-no-loading data-remove-bundle-row>Remove</button>
            `;
            return row;
        }

        bundleToggle?.addEventListener('change', toggleBundlePanel);
        addBundleRow?.addEventListener('click', function () {
            bundleRows.appendChild(makeBundleRow());
        });
        document.addEventListener('click', function (event) {
            const remove = event.target.closest('[data-remove-bundle-row]');
            if (!remove) return;
            remove.closest('.bundle-row')?.remove();
        });
    })();
</script>
