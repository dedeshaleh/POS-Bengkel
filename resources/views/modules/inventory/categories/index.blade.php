@extends('layouts.app')

@section('title', 'Data Kategori Barang')
@section('subtitle', 'Manage product categories.')

@section('content')
<section class="panel" style="max-width:100%">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Data Kategori Barang</h2>
    </div>

    @if (session('status'))
        <div class="badge" style="background:#dcfce7;color:#166534;margin-bottom:12px">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice" style="border-color:#b42318;background:#fee2e2;margin-bottom:12px">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-grid" style="margin-bottom:20px">
        <form method="post" action="{{ route('modules.inventory.categories.store') }}" class="form-grid" style="grid-column:1 / -1; display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end">
            @csrf
            <label>
                Category Name
                <input type="text" name="name" required placeholder="e.g. Sparepart">
            </label>
            <label>
                SKU Prefix
                <input type="text" name="sku_prefix" placeholder="Auto if empty, e.g. SPR">
            </label>
            <div>
                <button class="btn">Save Category</button>
            </div>
        </form>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU Prefix</th>
                <th>Status</th>
                <th style="min-width:180px">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>
                        <form method="post" action="{{ route('modules.inventory.categories.update', $category) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                            @csrf @method('put')
                            <input type="text" name="name" value="{{ $category->name }}" required style="min-width:220px">
                            <input type="text" name="sku_prefix" value="{{ $category->sku_prefix }}" required style="min-width:120px">
                            <button class="btn secondary">Update</button>
                        </form>
                    </td>
                    <td>{{ $category->sku_prefix }}</td>
                    <td>{{ $category->is_active ? 'Active' : 'NonAktif' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            @if ($category->is_active)
                                <form method="post" action="{{ route('modules.inventory.categories.deactivate', $category) }}">
                                    @csrf @method('patch')
                                    <button class="btn" style="background:#b42318">NonAktif</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('modules.inventory.categories.activate', $category) }}">
                                    @csrf @method('patch')
                                    <button class="btn" style="background:#16794f">Aktifkan</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No categories yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.pager', ['paginator' => $categories])
</section>
@endsection
