@extends('layouts.app')
@section('title', 'Add Menu')
@section('subtitle', 'Create new menu item.')
@section('content')
<section class="panel" style="max-width:760px">
    <h2>New Menu Form</h2>
    <form method="post" action="{{ route('master.menus.store') }}" class="form-grid">
        @csrf
        <label>Name <input name="name" required></label>
        <label>URL <input name="url" placeholder="/master/users" required></label>
        <label>Icon <input name="icon" placeholder="fa-solid fa-users"></label>
        <label>Sort Order <input type="number" min="0" name="sort_order" value="0" required></label>
        <label style="display:flex;align-items:center;gap:8px;flex-direction:row;font-weight:400">
            <input type="checkbox" name="is_progress" value="1">
            <span>On Progress (tandai menu sedang dikerjakan)</span>
        </label>
        <label class="full">Parent
            <select name="parent_id">
                <option value="">No parent</option>
                @foreach ($parentMenus as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="full" style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn">Save Menu</button>
            <a class="btn secondary" href="{{ route('master.menus') }}">Back</a>
        </div>
    </form>
</section>
@endsection

