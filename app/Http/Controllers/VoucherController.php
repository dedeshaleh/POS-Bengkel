<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::withCount('usages')
            ->with('products:id,sku,name')
            ->latest()
            ->paginate(15);

        return view('master.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->where('is_bundle', false)
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        return view('master.vouchers.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'nullable|string|max:150',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'scope_type' => 'required|in:transaction,item',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $voucher = DB::transaction(function () use ($data) {
            $voucher = Voucher::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'scope_type' => $data['scope_type'],
                'min_transaction_amount' => $data['min_transaction_amount'] ?? 0,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'usage_limit' => $data['usage_limit'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($data['scope_type'] === 'item' && !empty($data['product_ids'])) {
                $voucher->products()->sync($data['product_ids']);
            }

            return $voucher;
        });

        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$voucher->code} created.");
    }

    public function edit(Voucher $voucher)
    {
        $products = Product::where('is_active', true)
            ->where('is_bundle', false)
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        $voucher->load('products:id,sku,name');

        return view('master.vouchers.edit', compact('voucher', 'products'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'name' => 'nullable|string|max:150',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'scope_type' => 'required|in:transaction,item',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::transaction(function () use ($data, $voucher) {
            $voucher->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'scope_type' => $data['scope_type'],
                'min_transaction_amount' => $data['min_transaction_amount'] ?? 0,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'usage_limit' => $data['usage_limit'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($data['scope_type'] === 'item') {
                $voucher->products()->sync($data['product_ids'] ?? []);
            } else {
                $voucher->products()->detach();
            }
        });

        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$voucher->code} updated.");
    }

    public function destroy(Voucher $voucher)
    {
        $code = $voucher->code;
        $voucher->delete();
        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$code} deleted.");
    }
}
