<?php

namespace App\Http\Controllers;

use App\Models\CustomerDebt;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Resolve the requested date range, defaulting to the current month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);

        $base = fn () => Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from, $to]);

        $summary = (clone $base())
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(subtotal_amount), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $daily = (clone $base())
            ->selectRaw('DATE(sale_date) as d')
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('d')
            ->get();

        $byMethod = (clone $base())
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'unknown') as method")
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $byCashier = Sale::query()
            ->leftJoin('users', 'users.id', '=', 'sales.cashier_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->selectRaw("COALESCE(users.name, 'Tanpa Kasir') as cashier")
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as total')
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->get();

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->selectRaw('products.name, products.sku')
            ->selectRaw('SUM(sale_items.qty) as qty')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $recent = (clone $base())
            ->with('customer')
            ->latest('sale_date')
            ->limit(15)
            ->get();

        $openHeld = Sale::where('status', 'in_progress')->count();

        return view('reports.sales', compact(
            'from', 'to', 'summary', 'daily', 'byMethod', 'byCashier', 'topProducts', 'recent', 'openHeld'
        ));
    }

    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->range($request);

        $itemBase = fn () => SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to]);

        $totals = (clone $itemBase())
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0) as cogs')
            ->first();

        $revenue = (float) ($totals->revenue ?? 0);
        $cogs = (float) ($totals->cogs ?? 0);
        $grossProfit = $revenue - $cogs;
        $margin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        $tax = (float) Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$from, $to])
            ->sum('tax_amount');

        $daily = (clone $itemBase())
            ->selectRaw('DATE(sales.sale_date) as d')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0) as cogs')
            ->groupBy(DB::raw('DATE(sales.sale_date)'))
            ->orderBy('d')
            ->get();

        $byProduct = (clone $itemBase())
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('products.name, products.sku')
            ->selectRaw('SUM(sale_items.qty) as qty')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0) as cogs')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc(DB::raw('COALESCE(SUM(sale_items.subtotal), 0) - COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0)'))
            ->get();

        $unitsSold = (int) (clone $itemBase())->sum('sale_items.qty');

        $totalProfit = $byProduct->reduce(function ($carry, $p) {
            $profit = (float) $p->revenue - (float) $p->cogs;
            return $carry + ($profit > 0 ? $profit : 0);
        }, 0.0);

        $totalLoss = $byProduct->reduce(function ($carry, $p) {
            $profit = (float) $p->revenue - (float) $p->cogs;
            return $carry + ($profit < 0 ? abs($profit) : 0);
        }, 0.0);

        $byProduct = $byProduct->take(20);

        return view('reports.profit-loss', compact(
            'from', 'to', 'revenue', 'cogs', 'grossProfit', 'margin', 'tax', 'daily', 'byProduct',
            'unitsSold', 'totalProfit', 'totalLoss'
        ));
    }

    public function tax(Request $request)
    {
        [$from, $to] = $this->range($request);

        $base = fn () => Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from, $to]);

        $summary = (clone $base())
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(GREATEST(subtotal_amount - discount_amount, 0)), 0) as dpp')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as ppn')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $taxedTrx = (clone $base())->where('tax_amount', '>', 0)->count();

        $daily = (clone $base())
            ->selectRaw('DATE(sale_date) as d')
            ->selectRaw('COUNT(*) as trx')
            ->selectRaw('COALESCE(SUM(GREATEST(subtotal_amount - discount_amount, 0)), 0) as dpp')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as ppn')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('d')
            ->get();

        $transactions = (clone $base())
            ->where('tax_amount', '>', 0)
            ->with('customer')
            ->latest('sale_date')
            ->limit(30)
            ->get();

        return view('reports.tax', compact(
            'from', 'to', 'summary', 'taxedTrx', 'daily', 'transactions'
        ));
    }

    public function stock(Request $request)
    {
        $rows = Product::query()
            ->where('products.is_bundle', false)
            ->leftJoin('inventory_batches as ib', 'ib.product_id', '=', 'products.id')
            ->leftJoin('categories as c', 'c.id', '=', 'products.category_id')
            ->selectRaw('products.id, products.sku, products.name, products.total_stock, products.min_stock_level')
            ->selectRaw("COALESCE(c.name, 'Tanpa Kategori') as category")
            ->selectRaw('COALESCE(SUM(ib.current_qty * ib.base_uom_buy_price), 0) as stock_value')
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.total_stock', 'products.min_stock_level', 'c.name')
            ->orderByDesc('stock_value')
            ->get();

        $summary = [
            'skus' => $rows->count(),
            'units' => (int) $rows->sum('total_stock'),
            'value' => (float) $rows->sum('stock_value'),
            'low' => $rows->filter(fn ($r) => $r->total_stock > 0 && $r->total_stock <= $r->min_stock_level)->count(),
            'out' => $rows->filter(fn ($r) => $r->total_stock <= 0)->count(),
        ];

        $byCategory = $rows->groupBy('category')->map(fn ($g, $cat) => [
            'category' => $cat,
            'value' => (float) $g->sum('stock_value'),
            'units' => (int) $g->sum('total_stock'),
        ])->sortByDesc('value')->values();

        $topValue = $rows->take(10);

        return view('reports.stock', compact('rows', 'summary', 'byCategory', 'topValue'));
    }

    public function outstanding(Request $request)
    {
        $debts = CustomerDebt::query()
            ->with(['customer', 'sale'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get();

        $today = now()->startOfDay();

        $buckets = [
            'current' => ['label' => 'Belum Jatuh Tempo', 'amount' => 0.0, 'count' => 0],
            'd30' => ['label' => '1-30 Hari', 'amount' => 0.0, 'count' => 0],
            'd60' => ['label' => '31-60 Hari', 'amount' => 0.0, 'count' => 0],
            'd60plus' => ['label' => '> 60 Hari', 'amount' => 0.0, 'count' => 0],
        ];

        $overdueAmount = 0.0;
        $overdueCount = 0;

        foreach ($debts as $debt) {
            $remaining = (float) $debt->remaining_debt;
            $due = $debt->due_date ? Carbon::parse($debt->due_date)->startOfDay() : null;
            $daysOverdue = $due ? $due->diffInDays($today, false) : 0;

            if ($daysOverdue <= 0) {
                $key = 'current';
            } elseif ($daysOverdue <= 30) {
                $key = 'd30';
            } elseif ($daysOverdue <= 60) {
                $key = 'd60';
            } else {
                $key = 'd60plus';
            }

            $buckets[$key]['amount'] += $remaining;
            $buckets[$key]['count']++;

            if ($daysOverdue > 0) {
                $overdueAmount += $remaining;
                $overdueCount++;
            }

            $debt->days_overdue = $daysOverdue;
        }

        $summary = [
            'total' => (float) $debts->sum('remaining_debt'),
            'count' => $debts->count(),
            'overdue_amount' => $overdueAmount,
            'overdue_count' => $overdueCount,
        ];

        return view('reports.outstanding', compact('debts', 'summary', 'buckets'));
    }

    public function revenue(Request $request)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->subMonths(11)->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $itemBase = fn () => SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to]);

        $monthly = (clone $itemBase())
            ->selectRaw("to_char(sales.sale_date, 'YYYY-MM') as ym")
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0) as cogs')
            ->groupBy(DB::raw("to_char(sales.sale_date, 'YYYY-MM')"))
            ->orderBy('ym')
            ->get();

        $byCategory = (clone $itemBase())
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'products.category_id')
            ->selectRaw("COALESCE(c.name, 'Tanpa Kategori') as category")
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->groupBy('c.name')
            ->orderByDesc('revenue')
            ->get();

        $revenue = (float) $monthly->sum('revenue');
        $cogs = (float) $monthly->sum('cogs');
        $profit = $revenue - $cogs;
        $unitsSold = (int) (clone $itemBase())->sum('sale_items.qty');
        $trx = Sale::where('status', 'completed')->whereBetween('sale_date', [$from, $to])->count();
        $outstanding = (float) CustomerDebt::where('status', '!=', 'paid')->sum('remaining_debt');

        return view('reports.revenue', compact(
            'from', 'to', 'monthly', 'byCategory', 'revenue', 'cogs', 'profit', 'unitsSold', 'trx', 'outstanding'
        ));
    }
}
