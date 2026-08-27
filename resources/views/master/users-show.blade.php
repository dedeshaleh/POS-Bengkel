@extends('layouts.app')

@section('title', 'View User')
@section('subtitle', 'User details and role assignments.')

@section('content')
<section class="panel" style="max-width:760px">
    <h2>User Detail</h2>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Name</th><td>{{ $user->name }}</td></tr>
            <tr><th>Email</th><td>{{ $user->email }}</td></tr>
            <tr><th>Status</th><td>{{ $user->is_active ? 'Active' : 'NonAktif' }}</td></tr>
            <tr><th>Roles</th><td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
        <a href="{{ route('master.users.edit', $user) }}" class="btn secondary">Edit</a>
        @if ($user->is_active)
            <a href="{{ route('master.users.delete', $user) }}" class="btn" style="background:#b42318">NonAktif</a>
        @endif
        <a href="{{ route('master.users') }}" class="btn secondary">Back</a>
    </div>
</section>
@endsection
