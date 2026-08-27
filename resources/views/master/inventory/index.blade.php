@extends('layouts.app')

@section('title', 'Master Inventory')
@section('subtitle', 'Inventory product and category CRUD.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Product & Sparepart</h2>
        <a href="{{ route('master.inventory.create') }}" class="btn">Add Item</a>
    </div>
    <table class="table">
        <thead><tr><th>SKU</th><th>Barcode</th><th>Name</th><th>Type</th><th>Category</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->barcode ?: '-' }}</td>
                    <td>{{ $product->name }}</td>
                    <td><span class="badge">{{ $product->item_type_code }}</span></td>
                    <td>{{ $product->category?->name ?? '-' }}</td>
                    <td>{{ $product->is_bundle ? 'Bundle' : $product->total_stock . ' ' . $product->base_uom_code }}</td>
                    <td>{{ $product->is_active ? 'Active' : 'NonAktif' }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.inventory.show', $product) }}">View</a>
                        <a class="btn secondary" href="{{ route('master.inventory.edit', $product) }}">Edit</a>
                        @if ($product->is_active)
                            <form method="post" action="{{ route('master.inventory.deactivate', $product) }}">@csrf @method('patch')<button class="btn" style="background:#b42318">NonAktif</button></form>
                        @else
                            <form method="post" action="{{ route('master.inventory.activate', $product) }}">@csrf @method('patch')<button class="btn" style="background:#16794f">Aktifkan</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No inventory items yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $products])
@endsection
