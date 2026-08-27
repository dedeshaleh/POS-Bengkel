@extends('layouts.app')
@section('title', 'Edit Role Access')
@section('subtitle', 'Update CRUD permissions for a role-menu pair.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>Edit Role Access</h2>
    <form method="post" action="{{ route('master.menus.access.update', $access) }}" class="form-grid">
        @csrf
        @method('put')
        <label>Role
            <select name="role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ (int)$access->role_id === (int)$role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Menu
            <select name="menu_id" required>
                @foreach ($menus as $menu)
                    <option value="{{ $menu->id }}" {{ (int)$access->menu_id === (int)$menu->id ? 'selected' : '' }}>{{ $menu->name }} ({{ $menu->url }})</option>
                @endforeach
            </select>
        </label>
        <label><input type="checkbox" name="can_read" value="1" style="width:auto" {{ $access->can_read ? 'checked' : '' }}> Can Read</label>
        <label><input type="checkbox" name="can_create" value="1" style="width:auto" {{ $access->can_create ? 'checked' : '' }}> Can Create</label>
        <label><input type="checkbox" name="can_update" value="1" style="width:auto" {{ $access->can_update ? 'checked' : '' }}> Can Update</label>
        <label><input type="checkbox" name="can_delete" value="1" style="width:auto" {{ $access->can_delete ? 'checked' : '' }}> Can Delete</label>
        <div class="full" style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn secondary">Update Access</button>
            <a class="btn secondary" href="{{ route('master.menus') }}">Back</a>
        </div>
    </form>
</section>
@endsection

