<?php

namespace App\Http\Controllers;

use App\Models\CustomerDebt;
use App\Models\Product;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->startOfDay();

        $lowStock = Product::whereColumn('total_stock', '<=', 'min_stock_level')
            ->where('total_stock', '>', 0)
            ->where('is_bundle', false)
            ->orderBy('total_stock')
            ->limit(20)
            ->get();

        $outOfStock = Product::where('total_stock', '<=', 0)
            ->where('is_bundle', false)
            ->orderBy('name')
            ->limit(20)
            ->get();

        $overdueDebts = CustomerDebt::with('customer')
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        return view('dashboard', [
            'salesToday' => Sale::whereDate('sale_date', now())->sum('grand_total'),
            'openDebts' => CustomerDebt::where('status', '!=', 'paid')->sum('remaining_debt'),
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'overdueDebts' => $overdueDebts,
            'recentSales' => Sale::with('customer')->latest('sale_date')->limit(6)->get(),
        ]);
    }

    public function alerts()
    {
        $today = now()->startOfDay();

        return response()->json([
            'low_stock' => Product::whereColumn('total_stock', '<=', 'min_stock_level')
                ->where('total_stock', '>', 0)
                ->where('is_bundle', false)
                ->count(),
            'out_of_stock' => Product::where('total_stock', '<=', 0)
                ->where('is_bundle', false)
                ->count(),
            'outstanding_bills' => CustomerDebt::where('status', '!=', 'paid')->count(),
            'overdue_bills' => CustomerDebt::where('status', '!=', 'paid')
                ->whereDate('due_date', '<', $today)
                ->count(),
            'low_stock_items' => Product::whereColumn('total_stock', '<=', 'min_stock_level')
                ->where('total_stock', '>', 0)
                ->where('is_bundle', false)
                ->orderBy('total_stock')
                ->limit(10)
                ->get(['id', 'sku', 'name', 'total_stock', 'min_stock_level'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'stock' => $p->total_stock,
                    'min' => $p->min_stock_level,
                ]),
            'out_of_stock_items' => Product::where('total_stock', '<=', 0)
                ->where('is_bundle', false)
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'sku', 'name'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                ]),
            'overdue_items' => CustomerDebt::with('customer')
                ->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->limit(10)
                ->get(['id', 'sale_id', 'customer_id', 'remaining_debt', 'due_date'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'customer' => $d->customer?->name ?? '-',
                    'remaining' => (float) $d->remaining_debt,
                    'due_date' => $d->due_date?->toDateString(),
                    'days_overdue' => $d->due_date ? $today->diffInDays($d->due_date, false) * -1 : 0,
                ]),
        ]);
    }
}
