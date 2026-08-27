@extends('layouts.app')
@section('title', 'Edit Menu')
@section('subtitle', 'Update menu configuration.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>Edit Menu Form</h2>
    <form method="post" action="{{ route('master.menus.update', $menu) }}" class="form-grid">
        @csrf
        @method('put')
        <label>Name <input name="name" value="{{ $menu->name }}" required></label>
        <label>URL <input name="url" value="{{ $menu->url }}" required></label>
        <label>Icon <input name="icon" value="{{ $menu->icon }}"></label>
        <label>Sort Order <input type="number" min="0" name="sort_order" value="{{ $menu->sort_order }}" required></label>
        <label style="display:flex;align-items:center;gap:8px;flex-direction:row;font-weight:400">
            <input type="hidden" name="is_progress" value="0">
            <input type="checkbox" name="is_progress" value="1" {{ $menu->is_progress ? 'checked' : '' }}>
            <span>On Progress (tandai menu sedang dikerjakan)</span>
        </label>
        <label class="full">Parent
            <select name="parent_id">
                <option value="">No parent</option>
                @foreach ($parentMenus as $parent)
                    <option value="{{ $parent->id }}" {{ (int)$menu->parent_id === (int)$parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="full" style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn secondary">Update Menu</button>
            <a class="btn secondary" href="{{ route('master.menus') }}">Back</a>
        </div>
    </form>
</section>
@endsection

