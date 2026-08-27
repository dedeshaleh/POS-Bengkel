@extends('layouts.app')

@section('title', 'Retur Penjualan Baru')
@section('subtitle', 'Terima barang retur dari customer.')

@section('content')
<section class="panel" style="max-width:900px">
    <h2>Retur Penjualan Baru</h2>
    <form method="post" action="{{ route('returns.sales.store') }}">
        @csrf
        <div class="form-grid">
            <label>
                Invoice Referensi (opsional)
                <select name="sale_id">
                    <option value="">— Pilih Invoice —</option>
                    @foreach ($sales as $sale)
                        <option value="{{ $sale->id }}">{{ $sale->receipt_number }} ({{ $sale->customer?->name ?? '-' }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                Customer
                <select name="customer_id">
                    <option value="">— Pilih Customer —</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Tanggal Retur
                <input type="date" name="return_date" value="{{ old('return_date', now()->format('Y-m-d')) }}" required>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="draft">Draft</option>
                    <option value="approved">Approved (langsung kembalikan stok)</option>
                </select>
            </label>
            <label class="full">
                Alasan
                <input type="text" name="reason" value="{{ old('reason') }}" maxlength="200">
            </label>
            <label class="full">
                Catatan
                <textarea name="note" rows="2">{{ old('note') }}</textarea>
            </label>
        </div>

        <h3 style="margin:20px 0 10px">Items</h3>
        <table class="table" id="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Buy Price</th>
                    <th>Selling Price</th>
                    <th>Alasan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="items-body">
                <tr class="item-row">
                    <td>
                        <select name="items[0][product_id]" required>
                            <option value="">— Pilih Produk —</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="items[0][qty]" min="1" value="1" required></td>
                    <td><input type="number" name="items[0][buy_price]" step="0.01" min="0" value="0" required></td>
                    <td><input type="number" name="items[0][selling_price]" step="0.01" min="0" value="0" required></td>
                    <td><input type="text" name="items[0][reason]" maxlength="200"></td>
                    <td><button type="button" class="btn secondary" onclick="removeRow(this)" style="background:#b42318">Hapus</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn secondary" id="add-row">+ Tambah Item</button>

        <div style="margin-top:20px">
            <button type="submit" class="btn">Simpan Retur</button>
            <a class="btn secondary" href="{{ route('returns.sales.index') }}">Batal</a>
        </div>
    </form>
</section>

<script>
let rowIndex = 1;
document.getElementById('add-row').addEventListener('click', function () {
    const tbody = document.getElementById('items-body');
    const row = tbody.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input, select').forEach(function (el) {
        const name = el.getAttribute('name');
        if (name) el.setAttribute('name', name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']'));
        if (el.tagName === 'INPUT') el.value = el.type === 'number' ? '0' : '';
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
    });
    tbody.appendChild(row);
    rowIndex++;
});

function removeRow(btn) {
    const rows = document.querySelectorAll('#items-body .item-row');
    if (rows.length > 1) btn.closest('.item-row').remove();
}
</script>
@endsection
