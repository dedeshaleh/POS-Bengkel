@extends('layouts.app')

@section('title', 'Add User')
@section('subtitle', 'Create new user and assign one or more roles.')

@section('content')
<section class="panel" style="max-width:760px">
    <h2>New User Form</h2>
    <form method="post" action="{{ route('master.users.store') }}" class="form-grid">
        @csrf
        <label>Name <input name="name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Password <input type="password" name="password" required></label>
        <label class="full">Roles (Multi Select)
            <select name="role_ids[]" multiple size="6" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <div style="display:flex;gap:8px;flex-wrap:wrap" class="full">
            <button class="btn">Save User</button>
            <a href="{{ route('master.users') }}" class="btn secondary">Back</a>
        </div>
    </form>
</section>
@endsection
