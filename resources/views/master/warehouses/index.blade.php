@extends('layouts.app')

@section('title', 'Master Gudang')
@section('subtitle', 'Warehouse hierarchy and rack management.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Warehouse List</h2>
        <a href="{{ route('master.warehouses.create') }}" class="btn">Add Warehouse</a>
    </div>
    <table class="table">
        <thead><tr><th>Code</th><th>Name</th><th>Parent</th><th>Racks</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($warehouses as $warehouse)
                <tr>
                    <td>{{ $warehouse->code }}</td>
                    <td>{{ $warehouse->name }}</td>
                    <td>{{ $warehouse->parent?->name ?: '-' }}</td>
                    <td><a href="{{ route('master.warehouses.racks.index', $warehouse) }}">{{ $warehouse->allRacks->count() }} rack(s)</a></td>
                    <td>{{ $warehouse->is_active ? 'Active' : 'NonAktif' }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.warehouses.edit', $warehouse) }}">Edit</a>
                        @if ($warehouse->is_active)
                            <form method="post" action="{{ route('master.warehouses.deactivate', $warehouse) }}">@csrf @method('patch')<button class="btn" style="background:#b42318">NonAktif</button></form>
                        @else
                            <form method="post" action="{{ route('master.warehouses.activate', $warehouse) }}">@csrf @method('patch')<button class="btn" style="background:#16794f">Aktifkan</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No warehouses yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $warehouses])
@endsection
