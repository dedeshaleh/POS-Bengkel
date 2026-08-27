@extends('layouts.app')

@section('title', 'Master User')
@section('subtitle', 'Manage users with dedicated add, view, edit, and nonaktif pages.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>User List</h2>
        <a href="{{ route('master.users.create') }}" class="btn">Add User</a>
    </div>

    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge">Active</span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#991b1b">NonAktif</span>
                        @endif
                    </td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.users.show', $user) }}">View</a>
                        <a class="btn secondary" href="{{ route('master.users.edit', $user) }}">Edit</a>
                        @if ($user->is_active)
                            <a class="btn" style="background:#b42318" href="{{ route('master.users.delete', $user) }}">NonAktif</a>
                        @else
                            <form method="post" action="{{ route('master.users.activate', $user) }}">
                                @csrf
                                @method('patch')
                                <button class="btn" style="background:#16794f" type="submit">Aktifkan</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No users yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $users])
@endsection
