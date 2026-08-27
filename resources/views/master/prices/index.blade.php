@extends('layouts.app')

@section('title', 'Master Harga')
@section('subtitle', 'Price catalog with active base price and historical effective dates.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Price Catalog</h2>
        <a class="btn" href="{{ route('master.prices.import') }}">Import CSV</a>
    </div>
    <form method="get" class="form-grid" style="margin-bottom:12px">
        <label>Search <input name="q" value="{{ request('q') }}" placeholder="SKU / name / barcode"></label>
        <label>Category
            <select name="category_id">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Item Type <input name="item_type_code" value="{{ request('item_type_code') }}" placeholder="SPAREPART / SERVICE"></label>
        <div style="display:flex;align-items:end;gap:8px"><button class="btn secondary">Filter</button><a class="btn secondary" href="{{ route('master.prices.index') }}">Reset</a></div>
    </form>
    <table class="table">
        <thead><tr><th>SKU</th><th>Item</th><th>Current Base Price</th><th>Markup</th><th>Estimated Selling</th><th>Effective Start</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}<div class="muted">{{ $product->category?->name }} | {{ $product->item_type_code }}</div></td>
                    <td>Rp {{ number_format($product->activePrice?->base_price ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $product->markup_type === 'fixed' ? 'Fixed Rp ' . number_format($product->markup_value, 0, ',', '.') : number_format($product->markup_value, 2, ',', '.') . '%' }}</td>
                    <td>Rp {{ number_format($priceService->calculateSellingPrice($product), 0, ',', '.') }}</td>
                    <td>{{ $product->activePrice?->effective_date_start?->format('d M Y') ?? '-' }}</td>
                    <td>{!! $product->activePrice ? '<span class="badge">Active</span>' : '<span class="badge" style="background:#fee2e2;color:#991b1b">No Price</span>' !!}</td>
                    <td><a class="btn secondary" href="{{ route('master.prices.history', $product) }}">History</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No items found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $products])
@endsection
