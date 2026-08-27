@extends('layouts.app')

@section('title', 'Master Role')
@section('subtitle', 'Create roles used by users and menu access.')

@section('content')
<div class="grid two">
    <section class="panel">
        <h2>Add Role</h2>
        <form method="post" action="{{ route('master.roles.store') }}" class="form-grid">
            @csrf
            <label class="full">Role Name <input name="name" required></label>
            <div class="full"><button class="btn">Save Role</button></div>
        </form>
    </section>
    <section class="panel">
        <h2>Role List</h2>
        <table class="table">
            <thead><tr><th>Name</th><th>Total Users</th></tr></thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->users_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">No roles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@include('partials.pager', ['paginator' => $roles])
@endsection
