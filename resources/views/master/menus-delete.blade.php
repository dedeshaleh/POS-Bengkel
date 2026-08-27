@extends('layouts.app')
@section('title', 'Delete Menu')
@section('subtitle', 'Confirm menu deletion.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>Delete Menu</h2>
    <p class="muted">Menu ini akan dihapus permanen termasuk relasi anak karena foreign key cascade.</p>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Name</th><td>{{ $menu->name }}</td></tr>
            <tr><th>URL</th><td>{{ $menu->url }}</td></tr>
            <tr><th>Parent</th><td>{{ $menu->parent?->name ?? '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <form method="post" action="{{ route('master.menus.destroy', $menu) }}">
            @csrf
            @method('delete')
            <button class="btn" style="background:#b42318">Confirm Delete</button>
        </form>
        <a class="btn secondary" href="{{ route('master.menus') }}">Cancel</a>
    </div>
</section>
@endsection

