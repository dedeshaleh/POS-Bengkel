<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceOrderController extends Controller
{
    public function index()
    {
        $activeStatuses = ['pending', 'in_progress'];
        $activeOrders = ServiceOrder::with(['customer', 'mechanic'])
            ->withCount('items')
            ->whereIn('status', $activeStatuses)
            ->latest()
            ->get();

        $completedOrders = ServiceOrder::with(['customer', 'mechanic', 'sale'])
            ->withCount('items')
            ->whereNotIn('status', $activeStatuses)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('service_orders.index', compact('activeOrders', 'completedOrders'));
    }

    public function create()
    {
        return view('service_orders.create', [
            'customers' => Customer::orderBy('name')->get(),
            'mechanics' => User::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('is_bundle', false)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ServiceOrderService $service)
    {
        $data = $request->validate($this->rules());

        try {
            $serviceOrder = $service->create($data);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('status', 'Service order saved.');
    }

    public function show(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load(['customer', 'mechanic', 'items.product', 'items.batch', 'sale']);

        return view('service_orders.show', compact('serviceOrder'));
    }

    public function edit(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('items.product', 'items.batch');

        return view('service_orders.edit', [
            'serviceOrder' => $serviceOrder,
            'customers' => Customer::orderBy('name')->get(),
            'mechanics' => User::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('is_bundle', false)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ServiceOrder $serviceOrder, ServiceOrderService $service)
    {
        $data = $request->validate($this->rules($serviceOrder));

        try {
            $service->update($serviceOrder, $data);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('status', 'Service order updated.');
    }

    public function completeAndPay(Request $request, ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->sale_id) {
            return redirect()->route('modules.pos.payment', $serviceOrder->sale_id);
        }

        if (!in_array($serviceOrder->status, ['pending', 'in_progress'])) {
            return back()->with('error', 'Service order sudah selesai atau dibatalkan.');
        }

        try {
            $sale = DB::transaction(function () use ($serviceOrder) {
                $sale = Sale::create([
                    'receipt_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad((string) (Sale::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT),
                    'customer_id' => $serviceOrder->customer_id,
                    'cashier_id' => auth()->id(),
                    'status' => 'in_progress',
                    'payment_status' => 'unpaid',
                    'subtotal_amount' => $serviceOrder->total_amount,
                    'discount_amount' => 0,
                    'tax_percentage' => 0,
                    'tax_amount' => 0,
                    'grand_total' => $serviceOrder->total_amount,
                ]);

                foreach ($serviceOrder->items as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item->product_id,
                        'inventory_batch_id' => $item->inventory_batch_id,
                        'qty' => $item->qty,
                        'buy_price' => $item->buy_price,
                        'base_selling_price' => $item->selling_price,
                        'discount_amount' => 0,
                        'final_selling_price' => $item->selling_price,
                        'subtotal' => $item->subtotal,
                    ]);
                }

                $serviceOrder->update([
                    'status' => 'completed',
                    'sale_id' => $sale->id,
                ]);

                return $sale;
            });

            return redirect()->route('modules.pos.payment', $sale->id);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function sendToPos(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->sale_id) {
            return redirect()->route('modules.pos.open-cashier', ['edit' => $serviceOrder->sale_id]);
        }

        if (!in_array($serviceOrder->status, ['pending', 'in_progress'])) {
            return back()->with('error', 'Service order sudah selesai atau dibatalkan.');
        }

        try {
            $sale = DB::transaction(function () use ($serviceOrder) {
                $sale = Sale::create([
                    'receipt_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad((string) (Sale::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT),
                    'customer_id' => $serviceOrder->customer_id,
                    'cashier_id' => auth()->id(),
                    'status' => 'in_progress',
                    'payment_status' => 'unpaid',
                    'subtotal_amount' => $serviceOrder->total_amount,
                    'discount_amount' => 0,
                    'tax_percentage' => 0,
                    'tax_amount' => 0,
                    'grand_total' => $serviceOrder->total_amount,
                ]);

                foreach ($serviceOrder->items as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item->product_id,
                        'inventory_batch_id' => $item->inventory_batch_id,
                        'qty' => $item->qty,
                        'buy_price' => $item->buy_price,
                        'base_selling_price' => $item->selling_price,
                        'discount_amount' => 0,
                        'final_selling_price' => $item->selling_price,
                        'subtotal' => $item->subtotal,
                    ]);
                }

                $serviceOrder->update([
                    'status' => 'completed',
                    'sale_id' => $sale->id,
                ]);

                return $sale;
            });

            return redirect()->route('modules.pos.open-cashier', ['edit' => $sale->id]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function rules(?ServiceOrder $serviceOrder = null): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'estimated_completion' => ['nullable', 'date'],
            'notes' => ['nullable'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in(['sparepart', 'service', 'other'])],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.item_name' => ['nullable', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.buy_price' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable'],
        ];
    }
}
