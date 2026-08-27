@extends('layouts.app')
@section('title', 'Add Role Access')
@section('subtitle', 'Assign role permissions to menu.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>New Role Access</h2>
    <form method="post" action="{{ route('master.menus.access') }}" class="form-grid">
        @csrf
        <label>Role
            <select name="role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Menu
            <select name="menu_id" required>
                @foreach ($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->name }} ({{ $menu->url }})</option>
                @endforeach
            </select>
        </label>
        <label><input type="checkbox" name="can_read" value="1" style="width:auto"> Can Read</label>
        <label><input type="checkbox" name="can_create" value="1" style="width:auto"> Can Create</label>
        <label><input type="checkbox" name="can_update" value="1" style="width:auto"> Can Update</label>
        <label><input type="checkbox" name="can_delete" value="1" style="width:auto"> Can Delete</label>
        <div class="full" style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn">Save Access</button>
            <a class="btn secondary" href="{{ route('master.menus') }}">Back</a>
        </div>
    </form>
</section>
@endsection

