@extends('layouts.app')

@section('title', 'Master Menu')
@section('subtitle', 'Friendly CRUD for menus and role access.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Menu List</h2>
        <a href="{{ route('master.menus.create') }}" class="btn">Add Menu</a>
    </div>
    <table class="table">
        <thead><tr><th>Name</th><th>URL</th><th>Parent</th><th>Sort</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($menus as $menu)
                <tr>
                    <td>{{ $menu->name }}</td>
                    <td>{{ $menu->url }}</td>
                    <td>{{ $menu->parent?->name ?? '-' }}</td>
                    <td>{{ $menu->sort_order }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.menus.show', $menu) }}">View</a>
                        <a class="btn secondary" href="{{ route('master.menus.edit', $menu) }}">Edit</a>
                        <a class="btn" style="background:#b42318" href="{{ route('master.menus.delete', $menu) }}">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No menu data.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $menus])

<section class="panel" style="margin-top:16px">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Role Access List</h2>
        <a href="{{ route('master.menus.access.create') }}" class="btn">Add Access</a>
    </div>
    <table class="table">
        <thead><tr><th>Role</th><th>Menu</th><th>R</th><th>C</th><th>U</th><th>D</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($roleAccesses as $access)
                <tr>
                    <td>{{ $access->role?->name ?? '-' }}</td>
                    <td>{{ $access->menu?->name ?? '-' }}</td>
                    <td>{{ $access->can_read ? 'Y' : '-' }}</td>
                    <td>{{ $access->can_create ? 'Y' : '-' }}</td>
                    <td>{{ $access->can_update ? 'Y' : '-' }}</td>
                    <td>{{ $access->can_delete ? 'Y' : '-' }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.menus.access.edit', $access) }}">Edit</a>
                        <a class="btn" style="background:#b42318" href="{{ route('master.menus.access.delete', $access) }}">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No role access data.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $roleAccesses])
@endsection
