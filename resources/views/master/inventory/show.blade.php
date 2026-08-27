@extends('layouts.app')

@section('title', 'Detail Inventory Item')
@section('subtitle', 'Informasi detail produk, sparepart, atau bundle.')

@section('content')
<section class="panel" style="max-width:920px">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Detail Inventory Item</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn secondary" href="{{ route('master.inventory.index') }}">Back</a>
            <a class="btn secondary" href="{{ route('master.inventory.edit', $product) }}">Edit</a>
        </div>
    </div>

    <table class="table">
        <tbody>
            <tr>
                <th style="width:220px">SKU</th>
                <td>{{ $product->sku }}</td>
            </tr>
            <tr>
                <th>Barcode / QR Value</th>
                <td>{{ $product->barcode ?: '-' }}</td>
            </tr>
            <tr>
                <th>Name</th>
                <td>{{ $product->name }}</td>
            </tr>
            <tr>
                <th>Category</th>
                <td>{{ $product->category?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Item Type</th>
                <td><span class="badge">{{ $product->item_type_code }}</span></td>
            </tr>
            <tr>
                <th>Base UOM</th>
                <td>{{ $product->base_uom_code }}</td>
            </tr>
            <tr>
                <th>Stock</th>
                <td>{{ $product->is_bundle ? 'Bundle' : $product->total_stock . ' ' . $product->base_uom_code }}</td>
            </tr>
            <tr>
                <th>Minimum Stock</th>
                <td>{{ $product->min_stock_level }}</td>
            </tr>
            <tr>
                <th>Markup</th>
                <td>{{ ucfirst($product->markup_type) }} - {{ number_format((float) $product->markup_value, 2) }}</td>
            </tr>
            <tr>
                <th>Selling Price</th>
                <td>{{ number_format($product->sellingPrice(), 2) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $product->is_active ? 'Active' : 'NonAktif' }}</td>
            </tr>
        </tbody>
    </table>
</section>

@if ($product->is_bundle)
    <section class="panel" style="max-width:920px;margin-top:16px">
        <h2>Bundle Components</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Component</th>
                    <th>Qty</th>
                    <th>UOM</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($product->bundleItems as $bundleItem)
                    <tr>
                        <td>{{ $bundleItem->component?->sku ?? '-' }}</td>
                        <td>{{ $bundleItem->component?->name ?? '-' }}</td>
                        <td>{{ $bundleItem->qty }}</td>
                        <td>{{ $bundleItem->component?->base_uom_code ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No bundle components.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endif

@if (! $product->is_bundle)
    <section class="panel" style="max-width:920px;margin-top:16px">
        <h2>Inventory Batches</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Warehouse</th>
                    <th>Current Qty</th>
                    <th>Buy Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($product->batches as $batch)
                    <tr>
                        <td>{{ $batch->batch_no ?? $batch->id }}</td>
                        <td>{{ $batch->warehouse?->name ?? '-' }}</td>
                        <td>{{ $batch->current_qty }}</td>
                        <td>{{ number_format((float) $batch->base_uom_buy_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No inventory batch data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endif
@endsection
