<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\GlobalMaster;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index()
    {
        return view('master-data.index', [
            'products' => Product::with('category')->latest()->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('company_name')->get(),
            'uoms' => GlobalMaster::where('category_code', 'UOM')->orderBy('name')->get(),
            'itemTypes' => GlobalMaster::where('category_code', 'ITEM_TYPE')->orderBy('name')->get(),
            'sampleMenuTree' => Menu::with('children')
                ->whereNull('parent_id')
                ->where('url', '/sample-menu')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function storeProduct(Request $request)
    {
        Product::create($request->validate([
            'sku' => ['required', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'item_type_code' => ['required', 'max:50'],
            'base_uom_code' => ['required', 'max:50'],
            'markup_type' => ['required', 'in:percentage,fixed'],
            'markup_value' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'is_bundle' => ['nullable', 'boolean'],
        ]) + ['is_bundle' => $request->boolean('is_bundle')]);

        return back()->with('status', 'Product saved.');
    }

    public function storeCustomer(Request $request)
    {
        Customer::create($request->validate([
            'name' => ['required', 'max:100'],
            'phone' => ['nullable', 'max:20'],
            'license_plate' => ['nullable', 'max:20'],
        ]));

        return back()->with('status', 'Customer saved.');
    }

    public function storeSupplier(Request $request)
    {
        Supplier::create($request->validate([
            'company_name' => ['required', 'max:150'],
            'contact_person' => ['nullable', 'max:100'],
            'phone' => ['nullable', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable'],
        ]));

        return back()->with('status', 'Supplier saved.');
    }
}
