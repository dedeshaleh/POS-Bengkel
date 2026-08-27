<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('master.suppliers.index', [
            'suppliers' => Supplier::orderBy('company_name')->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('master.suppliers.create');
    }

    public function store(Request $request)
    {
        Supplier::create($this->validatedData($request));

        return redirect()->route('master.suppliers.index')->with('status', 'Supplier saved.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['supplierProducts.product']);

        return view('master.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('master.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validatedData($request));

        return redirect()->route('master.suppliers.index')->with('status', 'Supplier updated.');
    }

    public function deactivate(Supplier $supplier)
    {
        $supplier->update(['is_active' => false]);

        return redirect()->route('master.suppliers.index')->with('status', 'Supplier set to NonAktif.');
    }

    public function activate(Supplier $supplier)
    {
        $supplier->update(['is_active' => true]);

        return redirect()->route('master.suppliers.index')->with('status', 'Supplier activated.');
    }

    public function lookupProducts(Request $request, Supplier $supplier)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('sku', 'ilike', $term)
                        ->orWhere('name', 'ilike', $term)
                        ->orWhere('item_type_code', 'ilike', $term)
                        ->orWhere('base_uom_code', 'ilike', $term);
                });
            })
            ->orderBy('sku')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn (Product $product) => [
                'value' => $product->id,
                'label' => "{$product->sku} - {$product->name}",
                'description' => "Type: {$product->item_type_code} | UOM: {$product->base_uom_code}",
            ])->values(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function attachProduct(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'supplier_sku' => ['nullable', 'max:100'],
        ]);

        $supplier->products()->syncWithoutDetaching([
            $data['product_id'] => [
                'supplier_sku' => $data['supplier_sku'] ?? null,
                'is_active' => true,
            ],
        ]);

        return redirect()->route('master.suppliers.show', $supplier)->with('status', 'Supplier product mapping saved.');
    }

    public function detachProduct(Supplier $supplier, Product $product)
    {
        $supplier->products()->detach($product->id);

        return redirect()->route('master.suppliers.show', $supplier)->with('status', 'Supplier product mapping deleted.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'company_name' => ['required', 'max:150'],
            'contact_person' => ['nullable', 'max:100'],
            'phone' => ['nullable', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable'],
            'tax_id_npwp' => ['nullable', 'max:50'],
            'entity_type' => ['required', 'in:corporate,individual'],
            'pph21_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bank_account_info' => ['nullable'],
            'bank_name' => ['nullable', 'max:100'],
            'bank_account_name' => ['nullable', 'max:150'],
            'bank_account_number' => ['nullable', 'max:100'],
            'bank_branch' => ['nullable', 'max:150'],
            'is_ppn_enabled' => ['nullable', 'boolean'],
            'ppn_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['is_ppn_enabled'] = $request->boolean('is_ppn_enabled');
        $data['ppn_percentage'] = $data['is_ppn_enabled'] ? ($data['ppn_percentage'] ?? 11) : 0;
        $data['pph21_percentage'] = $data['entity_type'] === 'individual' ? ($data['pph21_percentage'] ?? 5) : 0;

        return $data;
    }
}
