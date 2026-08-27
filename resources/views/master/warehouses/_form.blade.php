@csrf
<div class="form-grid">
    <label>Code <input name="code" value="{{ old('code', $warehouse->code ?? '') }}" required></label>
    <label>Name <input name="name" value="{{ old('name', $warehouse->name ?? '') }}" required></label>
    <label>Parent Warehouse
        <select name="parent_id">
            <option value="">— Root (Top-Level) —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $warehouse->parent_id ?? '') == $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="full">Address <textarea name="address">{{ old('address', $warehouse->address ?? '') }}</textarea></label>
</div>
