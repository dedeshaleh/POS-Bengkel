@extends('layouts.app')

@section('title', 'Supplier Payable Detail')
@section('subtitle', $payable->supplier?->company_name ?? 'Supplier Payable')

@section('content')
@php
    $statusColor = match ($payable->status) {
        'paid' => 'background:#dcfce7;color:#166534',
        'partial' => 'background:#dbeafe;color:#1d4ed8',
        default => 'background:#fee2e2;color:#991b1b',
    };
@endphp

<section class="panel" style="max-width:980px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">{{ $payable->supplier?->company_name ?? 'No supplier' }}</h2>
            <div class="muted">Purchase: {{ $payable->purchase?->invoice_number ?? '-' }}</div>
            <div class="muted">Created by: {{ $payable->creator?->name ?? '-' }} | {{ $payable->created_at->format('d M Y H:i') }}</div>
        </div>
        <span class="badge" style="{{ $statusColor }}">{{ str($payable->status)->title() }}</span>
    </div>

    <div class="totals" style="display:grid;gap:8px;justify-content:end;margin-top:14px">
        <div><strong>Total Amount:</strong> Rp {{ number_format($payable->total_amount, 0, ',', '.') }}</div>
        <div><strong>Amount Paid:</strong> Rp {{ number_format($payable->amount_paid, 0, ',', '.') }}</div>
        <div><strong>Remaining:</strong> Rp {{ number_format($payable->remaining, 0, ',', '.') }}</div>
        <div><strong>Due Date:</strong> {{ $payable->due_date->format('d M Y') }}</div>
    </div>

    @if ($payable->notes)
        <div style="margin-top:14px">
            <strong>Notes:</strong>
            <div class="muted">{{ $payable->notes }}</div>
        </div>
    @endif

    <div class="action-bar" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        @if ($payable->remaining > 0)
            <a class="btn" href="{{ route('supplier-payables.pay', $payable) }}">
                <i class="fa-solid fa-money-bill-wave"></i>
                Pay
            </a>
        @endif
        <a class="btn secondary" href="{{ route('supplier-payables.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>
    </div>
</section>

<section class="panel" style="max-width:980px;margin-top:16px">
    <h2 style="margin-top:0">Payment History</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Cashier</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payable->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('d M Y H:i') }}</td>
                    <td>Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</td>
                    <td>{{ $payment->payment_method ?? '-' }}</td>
                    <td>{{ $payment->cashier?->name ?? '-' }}</td>
                    <td>{{ $payment->note ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No payments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
