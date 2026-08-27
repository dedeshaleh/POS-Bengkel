@extends('layouts.app')
@section('title', 'Delete Role Access')
@section('subtitle', 'Confirm role access deletion.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>Delete Role Access</h2>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Role</th><td>{{ $access->role?->name ?? '-' }}</td></tr>
            <tr><th>Menu</th><td>{{ $access->menu?->name ?? '-' }}</td></tr>
            <tr><th>Permissions</th><td>R: {{ $access->can_read ? 'Y' : '-' }}, C: {{ $access->can_create ? 'Y' : '-' }}, U: {{ $access->can_update ? 'Y' : '-' }}, D: {{ $access->can_delete ? 'Y' : '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <form method="post" action="{{ route('master.menus.access.destroy', $access) }}">
            @csrf
            @method('delete')
            <button class="btn" style="background:#b42318">Confirm Delete</button>
        </form>
        <a class="btn secondary" href="{{ route('master.menus') }}">Cancel</a>
    </div>
</section>
@endsection

