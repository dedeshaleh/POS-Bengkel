<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseRack;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('master.warehouses.index', [
            'warehouses' => Warehouse::with(['parent', 'allRacks' => fn ($q) => $q->with('children')])->orderBy('parent_id')->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('master.warehouses.create', [
            'parents' => Warehouse::root()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Warehouse::create($this->validatedData($request));

        return redirect()->route('master.warehouses.index')->with('status', 'Warehouse saved.');
    }

    public function edit(Warehouse $warehouse)
    {
        $warehouse->load('children');
        return view('master.warehouses.edit', [
            'warehouse' => $warehouse,
            'parents' => Warehouse::root()->where('id', '!=', $warehouse->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $warehouse->update($this->validatedData($request, $warehouse));

        return redirect()->route('master.warehouses.index')->with('status', 'Warehouse updated.');
    }

    public function deactivate(Warehouse $warehouse)
    {
        $warehouse->update(['is_active' => false]);

        return redirect()->route('master.warehouses.index')->with('status', 'Warehouse set to NonAktif.');
    }

    public function activate(Warehouse $warehouse)
    {
        $warehouse->update(['is_active' => true]);

        return redirect()->route('master.warehouses.index')->with('status', 'Warehouse activated.');
    }

    // --- Racks ---

    public function racks(Warehouse $warehouse)
    {
        $warehouse->load(['allRacks' => fn ($q) => $q->with('children')->root()->orderBy('code')]);

        return view('master.warehouses.racks', [
            'warehouse' => $warehouse,
        ]);
    }

    public function storeRack(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'code' => ['required', 'max:50', 'unique:warehouse_racks,code,NULL,id,warehouse_id,' . $warehouse->id],
            'name' => ['required', 'max:150'],
            'parent_rack_id' => ['nullable', 'exists:warehouse_racks,id'],
            'description' => ['nullable'],
        ]);

        $warehouse->allRacks()->create($data + ['is_active' => true]);

        return redirect()->route('master.warehouses.racks.index', $warehouse)->with('status', 'Rack saved.');
    }

    public function updateRack(Request $request, WarehouseRack $rack)
    {
        $data = $request->validate([
            'code' => ['required', 'max:50', 'unique:warehouse_racks,code,' . $rack->id . ',id,warehouse_id,' . $rack->warehouse_id],
            'name' => ['required', 'max:150'],
            'parent_rack_id' => ['nullable', 'exists:warehouse_racks,id'],
            'description' => ['nullable'],
        ]);

        $rack->update($data);

        return redirect()->route('master.warehouses.racks.index', $rack->warehouse_id)->with('status', 'Rack updated.');
    }

    public function deleteRack(WarehouseRack $rack)
    {
        $warehouseId = $rack->warehouse_id;
        $rack->delete();

        return redirect()->route('master.warehouses.racks.index', $warehouseId)->with('status', 'Rack deleted.');
    }

    public function activateRack(WarehouseRack $rack)
    {
        $rack->update(['is_active' => true]);

        return redirect()->route('master.warehouses.racks.index', $rack->warehouse_id)->with('status', 'Rack activated.');
    }

    public function deactivateRack(WarehouseRack $rack)
    {
        $rack->update(['is_active' => false]);

        return redirect()->route('master.warehouses.racks.index', $rack->warehouse_id)->with('status', 'Rack deactivated.');
    }

    // ---

    private function validatedData(Request $request, ?Warehouse $warehouse = null): array
    {
        return $request->validate([
            'code' => ['required', 'max:50', 'unique:warehouses,code,' . ($warehouse?->id ?? 'NULL')],
            'name' => ['required', 'max:150'],
            'address' => ['nullable'],
            'parent_id' => ['nullable', 'exists:warehouses,id'],
        ]) + ['is_active' => true];
    }
}
