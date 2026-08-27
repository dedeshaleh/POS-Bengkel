@extends('layouts.app')

@section('title', 'Racks — ' . $warehouse->name)
@section('subtitle', 'Kelola rak di ' . $warehouse->code)

@section('content')
<section class="panel" style="max-width:760px">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Racks</h2>
        <div style="display:flex;gap:6px">
            <a class="btn" href="#" onclick="document.getElementById('addRackForm').classList.toggle('hidden')">Add Rack</a>
            <a class="btn secondary" href="{{ route('master.warehouses.index') }}">Back</a>
        </div>
    </div>

    <form id="addRackForm" method="post" action="{{ route('master.warehouses.racks.store', $warehouse) }}" class="form-grid hidden" style="border:1px solid var(--border);padding:12px;border-radius:6px;margin-bottom:12px">
        @csrf
        <label>Code <input name="code" required></label>
        <label>Name <input name="name" required></label>
        <label>Parent Rack
            <select name="parent_rack_id">
                <option value="">— Top-Level Rack —</option>
                @foreach ($warehouse->allRacks as $rack)
                    <option value="{{ $rack->id }}">{{ $rack->code }} — {{ $rack->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="full">Description <textarea name="description"></textarea></label>
        <div class="full"><button class="btn">Save Rack</button></div>
    </form>

    @if ($warehouse->allRacks->count())
        <table class="table">
            <thead><tr><th>Code</th><th>Name</th><th>Parent Rack</th><th>Sub-Racks</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach ($warehouse->allRacks as $rack)
                    <tr>
                        <td>{{ $rack->code }}</td>
                        <td>{{ $rack->name }}</td>
                        <td>{{ $rack->parent?->name ?: '-' }}</td>
                        <td>
                            @if ($rack->children->count())
                                @foreach ($rack->children as $child)
                                    <span class="badge">{{ $child->code }} — {{ $child->name }}</span><br>
                                @endforeach
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $rack->is_active ? 'Active' : 'NonAktif' }}</td>
                        <td style="display:flex;gap:4px;flex-wrap:wrap">
                            <button class="btn secondary" onclick="editRack({{ $rack->id }}, '{{ $rack->code }}', '{{ $rack->name }}', '{{ addslashes($rack->description ?? '') }}', '{{ $rack->parent_rack_id }}')">Edit</button>
                            @if ($rack->is_active)
                                <form method="post" action="{{ route('master.warehouses.racks.deactivate', $rack) }}">@csrf @method('patch')<button class="btn" style="background:#b42318">NonAktif</button></form>
                            @else
                                <form method="post" action="{{ route('master.warehouses.racks.activate', $rack) }}">@csrf @method('patch')<button class="btn" style="background:#16794f">Aktifkan</button></form>
                            @endif
                            <form method="post" action="{{ route('master.warehouses.racks.delete', $rack) }}" onsubmit="return confirm('Delete this rack?')">@csrf @method('delete')<button class="btn" style="background:#b42318">Hapus</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No racks yet. Click "Add Rack" to create one.</p>
    @endif
</section>

{{-- Edit Rack Modal --}}
<div id="editModal" class="hidden" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:999">
    <div style="background:#fff;border-radius:8px;padding:24px;max-width:540px;width:90%;max-height:90vh;overflow:auto">
        <h3>Edit Rack</h3>
        <form method="post" id="editRackForm">
            @method('put')
            @csrf
            <div class="form-grid">
                <label>Code <input name="code" id="edit_code" required></label>
                <label>Name <input name="name" id="edit_name" required></label>
                <label>Parent Rack
                    <select name="parent_rack_id" id="edit_parent_rack_id">
                        <option value="">— Top-Level Rack —</option>
                        @foreach ($warehouse->allRacks as $rack)
                            <option value="{{ $rack->id }}">{{ $rack->code }} — {{ $rack->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="full">Description <textarea name="description" id="edit_description"></textarea></label>
                <div class="full" style="display:flex;gap:8px">
                    <button class="btn">Update Rack</button>
                    <button class="btn secondary" type="button" onclick="closeEdit()">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function editRack(id, code, name, description, parent_rack_id) {
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_parent_rack_id').value = parent_rack_id;
    document.getElementById('editRackForm').action = '/master/warehouses/racks/' + id;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
<style>
.hidden { display: none !important; }
</style>
@endsection
