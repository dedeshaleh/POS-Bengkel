@extends('layouts.app')

@section('title', 'Edit User')
@section('subtitle', 'Update user profile and multi-role assignments.')

@section('content')
<section class="panel" style="max-width:760px">
    <h2>Edit User Form</h2>
    <form method="post" action="{{ route('master.users.update', $user) }}" class="form-grid">
        @csrf
        @method('put')
        <label>Name <input name="name" value="{{ $user->name }}" required></label>
        <label>Email <input type="email" name="email" value="{{ $user->email }}" required></label>
        <label class="full">Password (Optional)
            <input type="password" name="password" placeholder="Leave empty to keep current password">
        </label>
        <label class="full">Roles (Multi Select)
            <select name="role_ids[]" multiple size="6" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ $user->roles->contains('id', $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <div style="display:flex;gap:8px;flex-wrap:wrap" class="full">
            <button class="btn secondary">Update User</button>
            <a href="{{ route('master.users') }}" class="btn secondary">Back</a>
        </div>
    </form>
</section>
@endsection
