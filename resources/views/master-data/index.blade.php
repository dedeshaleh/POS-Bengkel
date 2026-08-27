@extends('layouts.app')

@section('title', 'Master Data')
@section('subtitle', 'Products, customers, suppliers, UOM, and item references.')

@section('content')
<div class="grid two">
    <section class="panel">
        <h2>Add Product</h2>
        <form method="post" action="{{ route('products.store') }}" class="form-grid">
            @csrf
            <label>SKU <input name="sku" required></label>
            <label>Name <input name="name" required></label>
            <label>Category
                <select name="category_id">
                    <option value="">None</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Item Type
                <select name="item_type_code" required>
                    @foreach ($itemTypes as $type)
                        <option value="{{ $type->code }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Base UOM
                <select name="base_uom_code" required>
                    @foreach ($uoms as $uom)
                        <option value="{{ $uom->code }}">{{ $uom->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Markup Type
                <select name="markup_type">
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed</option>
                </select>
            </label>
            <label>Markup Value <input type="number" step="0.01" min="0" name="markup_value" value="25" required></label>
            <label>Minimum Stock <input type="number" min="0" name="min_stock_level" value="5" required></label>
            <label class="full"><input type="hidden" name="is_bundle" value="0"><span><input type="checkbox" name="is_bundle" value="1" style="width:auto"> Promo bundle / virtual product</span></label>
            <div class="full"><button class="btn">Save Product</button></div>
        </form>
    </section>

    <div class="grid">
        <section class="panel">
            <h2>Add Customer</h2>
            <form method="post" action="{{ route('customers.store') }}" class="form-grid">
                @csrf
                <label>Name <input name="name" required></label>
                <label>Phone <input name="phone"></label>
                <label>License Plate <input name="license_plate"></label>
                <div class="row-actions"><button class="btn">Save</button></div>
            </form>
        </section>

        <section class="panel">
            <h2>Add Supplier</h2>
            <form method="post" action="{{ route('suppliers.store') }}" class="form-grid">
                @csrf
                <label>Company <input name="company_name" required></label>
                <label>Contact <input name="contact_person"></label>
                <label>Phone <input name="phone"></label>
                <label>Email <input type="email" name="email"></label>
                <label class="full">Address <textarea name="address"></textarea></label>
                <div class="row-actions"><button class="btn">Save</button></div>
            </form>
        </section>
    </div>
</div>

<section class="panel" style="margin-top:16px">
    <h2>Products</h2>
    <table class="table">
        <thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Type</th><th>Stock</th><th>Bundle</th></tr></thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? '-' }}</td>
                    <td>{{ $product->item_type_code }}</td>
                    <td>{{ $product->is_bundle ? 'Virtual' : $product->total_stock . ' ' . $product->base_uom_code }}</td>
                    <td>{{ $product->is_bundle ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $products])

<section class="panel" style="margin-top:16px">
    <h2>Sample Menu Tree View</h2>
    <div class="muted" style="margin-bottom:10px">Sample 3 menu data from database in parent-child format.</div>
    <ul style="list-style:none;padding-left:0;margin:0;display:grid;gap:10px">
        @forelse ($sampleMenuTree as $root)
            <li style="border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px">
                <strong>{{ $root->name }}</strong>
                <div class="muted">URL: {{ $root->url }} | Icon: {{ $root->icon }} | Sort: {{ $root->sort_order }}</div>

                @if ($root->children->isNotEmpty())
                    <ul style="list-style:none;padding-left:18px;margin:10px 0 0;display:grid;gap:8px;border-left:2px dashed #cbd5e1">
                        @foreach ($root->children as $child)
                            <li style="padding-left:10px">
                                <strong>{{ $child->name }}</strong>
                                <div class="muted">URL: {{ $child->url }} | Icon: {{ $child->icon }} | Sort: {{ $child->sort_order }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @empty
            <li class="muted">No tree menu sample found. Run migrate --seed first.</li>
        @endforelse
    </ul>
</section>
@endsection
