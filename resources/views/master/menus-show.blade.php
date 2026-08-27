@extends('layouts.app')
@section('title', 'View Menu')
@section('subtitle', 'Menu detail information.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>Menu Detail</h2>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Name</th><td>{{ $menu->name }}</td></tr>
            <tr><th>URL</th><td>{{ $menu->url }}</td></tr>
            <tr><th>Icon</th><td>{{ $menu->icon }}</td></tr>
            <tr><th>Sort Order</th><td>{{ $menu->sort_order }}</td></tr>
            <tr><th>Parent</th><td>{{ $menu->parent?->name ?? '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <a class="btn secondary" href="{{ route('master.menus.edit', $menu) }}">Edit</a>
        <a class="btn" style="background:#b42318" href="{{ route('master.menus.delete', $menu) }}">Delete</a>
        <a class="btn secondary" href="{{ route('master.menus') }}">Back</a>
    </div>
</section>
@endsection

