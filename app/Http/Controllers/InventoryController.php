<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GlobalMaster;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return view('master.inventory.index', [
            'products' => Product::with('category')->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('master.inventory.create', $this->formData());
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->validatedProduct($request);
            $data['sku'] = $this->nextSku((int) $data['category_id']);
            $data['barcode'] = $data['barcode'] ?: $data['sku'];
            $product = Product::create($data);
            $this->syncBundleItems($request, $product);
        });

        return redirect()->route('master.inventory.index')->with('status', 'Inventory item saved.');
    }

    public function show(Product $product)
    {
        $product->load('category', 'batches.warehouse');

        return view('master.inventory.show', compact('product'));
    }

    public function printCode(Product $product)
    {
        return view('master.inventory.print-code', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('category', 'bundleItems.component');

        return view('master.inventory.edit', $this->formData() + compact('product'));
    }

    public function lookupCategories(Request $request)
    {
        $query = Category::query()
            ->where('is_active', true)
            ->when($request->filled('q'), fn ($builder) => $builder->where('name', 'ilike', '%' . $request->q . '%'))
            ->orderBy('name')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn ($category) => [
                'value' => $category->id,
                'label' => $category->name,
                'description' => 'Category #' . $category->id,
            ]),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function lookupGlobalMasters(Request $request, string $categoryCode)
    {
        $query = GlobalMaster::query()
            ->where('category_code', strtoupper($categoryCode))
            ->where('is_active', true)
            ->when($request->filled('q'), function ($builder) use ($request) {
                $builder->where(function ($search) use ($request) {
                    $search->where('code', 'ilike', '%' . $request->q . '%')
                        ->orWhere('name', 'ilike', '%' . $request->q . '%');
                });
            })
            ->orderBy('name')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn ($master) => [
                'value' => $master->code,
                'label' => $master->name,
                'description' => $master->code,
            ]),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function lookupComponents(Request $request)
    {
        $excludeId = $request->integer('exclude_id');
        $query = Product::query()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->when($excludeId, fn ($builder) => $builder->where('id', '!=', $excludeId))
            ->when($request->filled('q'), function ($builder) use ($request) {
                $builder->where(function ($search) use ($request) {
                    $search->where('sku', 'ilike', '%' . $request->q . '%')
                        ->orWhere('name', 'ilike', '%' . $request->q . '%');
                });
            })
            ->orderBy('name')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn ($product) => [
                'value' => $product->id,
                'label' => $product->sku . ' - ' . $product->name,
                'description' => 'Stock: ' . $product->total_stock . ' ' . $product->base_uom_code,
            ]),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $data = $this->validatedProduct($request, $product);
            $data['barcode'] = $data['barcode'] ?: $product->sku;
            $product->update($data);
            $this->syncBundleItems($request, $product);
        });

        return redirect()->route('master.inventory.index')->with('status', 'Inventory item updated.');
    }

    public function deactivate(Product $product)
    {
        $product->update(['is_active' => false]);

        return redirect()->route('master.inventory.index')->with('status', 'Inventory item set to NonAktif.');
    }

    public function activate(Product $product)
    {
        $product->update(['is_active' => true]);

        return redirect()->route('master.inventory.index')->with('status', 'Inventory item activated.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100', 'unique:categories,name'],
            'sku_prefix' => ['nullable', 'max:20', 'unique:categories,sku_prefix'],
        ]);
        $data['sku_prefix'] = $data['sku_prefix'] ?: $this->prefixFromName($data['name']);

        Category::create($data);

        return redirect()->route('master.inventory.index')->with('status', 'Category saved.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100', 'unique:categories,name,' . $category->id],
            'sku_prefix' => ['nullable', 'max:20', 'unique:categories,sku_prefix,' . $category->id],
        ]);
        $data['sku_prefix'] = $data['sku_prefix'] ?: $this->prefixFromName($data['name']);
        $category->update($data);

        return redirect()->route('master.inventory.index')->with('status', 'Category updated.');
    }

    public function deactivateCategory(Category $category)
    {
        $category->update(['is_active' => false]);

        return redirect()->route('master.inventory.index')->with('status', 'Category set to NonAktif.');
    }

    public function activateCategory(Category $category)
    {
        $category->update(['is_active' => true]);

        return redirect()->route('master.inventory.index')->with('status', 'Category activated.');
    }

    private function formData(): array
    {
        return [
            'uoms' => GlobalMaster::where('category_code', 'UOM')->where('is_active', true)->orderBy('name')->get(),
            'itemTypes' => GlobalMaster::where('category_code', 'ITEM_TYPE')->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        return $request->validate([
            'name' => ['required', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'barcode' => ['nullable', 'max:100', 'unique:products,barcode,' . $productId],
            'item_type_code' => ['required', 'max:50'],
            'base_uom_code' => ['required', 'max:50'],
            'is_bundle' => ['nullable', 'boolean'],
            'markup_type' => ['required', 'in:percentage,fixed'],
            'markup_value' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
        ]) + ['is_bundle' => $request->boolean('is_bundle')];
    }

    private function nextSku(int $categoryId): string
    {
        $category = Category::findOrFail($categoryId);
        $prefix = $category->sku_prefix ?: $this->prefixFromName($category->name);
        $lastSku = Product::where('sku', 'like', $prefix . '-%')->orderByDesc('sku')->value('sku');
        $nextNumber = $lastSku ? ((int) substr($lastSku, strrpos($lastSku, '-') + 1)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function prefixFromName(string $name): string
    {
        $words = preg_split('/\s+/', strtoupper(trim($name)));
        $prefix = collect($words)->filter()->map(fn ($word) => substr(preg_replace('/[^A-Z0-9]/', '', $word), 0, 3))->implode('');

        return substr($prefix ?: 'CAT', 0, 8);
    }

    private function syncBundleItems(Request $request, Product $product): void
    {
        if (! $product->is_bundle) {
            $product->bundleItems()->delete();
            return;
        }

        $data = $request->validate([
            'bundle_component_ids' => ['nullable', 'array'],
            'bundle_component_ids.*' => ['nullable', 'exists:products,id'],
            'bundle_component_qtys' => ['nullable', 'array'],
            'bundle_component_qtys.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $components = [];
        foreach (($data['bundle_component_ids'] ?? []) as $index => $componentId) {
            if (empty($componentId) || (int) $componentId === (int) $product->id) {
                continue;
            }

            $components[(int) $componentId] = [
                'qty' => (int) (($data['bundle_component_qtys'][$index] ?? 1) ?: 1),
            ];
        }

        $product->bundleItems()->delete();
        foreach ($components as $componentId => $payload) {
            $product->bundleItems()->create([
                'component_product_id' => $componentId,
                'qty' => $payload['qty'],
            ]);
        }
    }
}
