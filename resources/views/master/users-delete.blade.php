@extends('layouts.app')

@section('title', 'NonAktif User')
@section('subtitle', 'Confirm user deactivation (soft delete).')

@section('content')
<section class="panel" style="max-width:760px">
    <h2>Set User to NonAktif</h2>
    <p class="muted">User ini tidak akan bisa login, tapi data historinya tetap tersimpan.</p>
    <table class="table">
        <tbody>
            <tr><th style="width:180px">Name</th><td>{{ $user->name }}</td></tr>
            <tr><th>Email</th><td>{{ $user->email }}</td></tr>
            <tr><th>Roles</th><td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td></tr>
        </tbody>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
        <form method="post" action="{{ route('master.users.deactivate', $user) }}" onsubmit="return confirm('Set this user as NonAktif?')">
            @csrf
            @method('patch')
            <button class="btn" style="background:#b42318" type="submit">Confirm NonAktif</button>
        </form>
        <a href="{{ route('master.users') }}" class="btn secondary">Cancel</a>
    </div>
</section>
@endsection
