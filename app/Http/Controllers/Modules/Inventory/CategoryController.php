<?php

namespace App\Http\Controllers\Modules\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('modules.inventory.categories.index', [
            'categories' => Category::orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100', 'unique:categories,name'],
            'sku_prefix' => ['nullable', 'max:20', 'unique:categories,sku_prefix'],
        ]);
        $data['sku_prefix'] = $data['sku_prefix'] ?: $this->prefixFromName($data['name']);

        Category::create($data);

        return redirect()->route('modules.inventory.categories.index')->with('status', 'Category saved.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100', 'unique:categories,name,' . $category->id],
            'sku_prefix' => ['nullable', 'max:20', 'unique:categories,sku_prefix,' . $category->id],
        ]);
        $data['sku_prefix'] = $data['sku_prefix'] ?: $this->prefixFromName($data['name']);
        $category->update($data);

        return redirect()->route('modules.inventory.categories.index')->with('status', 'Category updated.');
    }

    public function deactivate(Category $category)
    {
        $category->update(['is_active' => false]);

        return redirect()->route('modules.inventory.categories.index')->with('status', 'Category set to NonAktif.');
    }

    public function activate(Category $category)
    {
        $category->update(['is_active' => true]);

        return redirect()->route('modules.inventory.categories.index')->with('status', 'Category activated.');
    }

    private function prefixFromName(string $name): string
    {
        $words = preg_split('/\s+/', strtoupper(trim($name)));
        $prefix = collect($words)->filter()->map(fn ($word) => substr(preg_replace('/[^A-Z0-9]/', '', $word), 0, 3))->implode('');

        return substr($prefix ?: 'CAT', 0, 8);
    }
}
