@extends('layouts.app')

@section('title', 'Pay Supplier')
@section('subtitle', $payable->supplier?->company_name ?? 'Supplier Payable')

@section('content')
<section class="panel" style="max-width:560px">
    <h2 style="margin-top:0">Record Payment</h2>

    <div class="muted" style="margin-bottom:12px">
        {{ $payable->purchase?->invoice_number ? 'Purchase: ' . $payable->purchase->invoice_number . ' | ' : '' }}
        Remaining: <span class="danger">Rp {{ number_format($payable->remaining, 0, ',', '.') }}</span>
    </div>

    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    <form method="post" action="{{ route('supplier-payables.pay.store', $payable) }}">
        @csrf
        <div class="form-grid">
            <label>Amount Paid
                <input type="number" step="0.01" min="0.01" max="{{ $payable->remaining }}" name="amount_paid" value="{{ old('amount_paid', $payable->remaining) }}" required>
            </label>

            <label>Payment Method
                <input name="payment_method" value="{{ old('payment_method') }}" placeholder="Cash, Transfer, etc">
            </label>

            <label>Payment Date
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}">
            </label>

            <label class="full">Note
                <textarea name="note" rows="3">{{ old('note') }}</textarea>
            </label>
        </div>

        <div class="row-actions" style="margin-top:16px">
            <button class="btn">Save Payment</button>
            <a class="btn secondary" href="{{ route('supplier-payables.show', $payable) }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
