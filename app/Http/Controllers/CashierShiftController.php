<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Sale;
use Illuminate\Http\Request;

class CashierShiftController extends Controller
{
    /**
     * List shifts (admin view).
     */
    public function index()
    {
        $shifts = CashierShift::with('user')
            ->latest('shift_date')
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        return view('cashier-shifts.index', compact('shifts'));
    }

    /**
     * Show the current cashier's shift status (open form to start or close).
     */
    public function status()
    {
        $shift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest()
            ->first();

        $sales = $shift
            ? $shift->sales()->with('customer')->latest()->limit(20)->get()
            : collect();

        return view('cashier-shifts.status', compact('shift', 'sales'));
    }

    /**
     * Open a new shift for the authenticated cashier.
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'max:500'],
        ]);

        $existing = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return back()->withErrors(['opening_cash' => 'Anda sudah punya shift yang masih terbuka. Tutup dulu shift sebelumnya.']);
        }

        CashierShift::create([
            'user_id' => auth()->id(),
            'shift_date' => now()->toDateString(),
            'opened_at' => now(),
            'opening_cash' => $data['opening_cash'],
            'note' => $data['note'] ?? null,
            'status' => 'open',
        ]);

        return redirect()->route('cashier-shifts.status')->with('status', 'Shift dibuka. Selamat bekerja.');
    }

    /**
     * Close the current cashier's shift with counted cash.
     */
    public function close(Request $request)
    {
        $data = $request->validate([
            'counted_closing_cash' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'max:500'],
        ]);

        $shift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest()
            ->firstOrFail();

        $expected = $shift->expectedCash();
        $counted = (float) $data['counted_closing_cash'];
        $difference = $counted - $expected;

        $shift->update([
            'closed_at' => now(),
            'counted_closing_cash' => $counted,
            'expected_closing_cash' => $expected,
            'cash_difference' => $difference,
            'status' => 'closed',
            'note' => $data['note'] ?? $shift->note,
        ]);

        return redirect()->route('cashier-shifts.status')->with('status', "Shift ditutup. Selisih kas: " . number_format($difference, 2));
    }

    /**
     * Show shift detail (reconciliation report).
     */
    public function show(CashierShift $cashierShift)
    {
        $cashierShift->load(['user', 'sales.customer']);
        return view('cashier-shifts.show', ['shift' => $cashierShift]);
    }
}
