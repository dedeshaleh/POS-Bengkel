@extends('layouts.app')

@section('title', 'Supplier Payables')
@section('subtitle', 'Track accounts payable and supplier payments.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Supplier Payables</h2>
        <a href="{{ route('supplier-payables.create') }}" class="btn">Add Payable</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Purchase</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payables as $payable)
                @php
                    $statusColor = match ($payable->status) {
                        'paid' => 'background:#dcfce7;color:#166534',
                        'partial' => 'background:#dbeafe;color:#1d4ed8',
                        default => 'background:#fee2e2;color:#991b1b',
                    };
                @endphp
                <tr>
                    <td>{{ $payable->supplier?->company_name ?? '-' }}</td>
                    <td>{{ $payable->purchase?->invoice_number ?? '-' }}</td>
                    <td>Rp {{ number_format($payable->total_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($payable->amount_paid, 0, ',', '.') }}</td>
                    <td><span class="{{ $payable->remaining > 0 ? 'danger' : '' }}">Rp {{ number_format($payable->remaining, 0, ',', '.') }}</span></td>
                    <td>{{ $payable->due_date->format('d M Y') }}</td>
                    <td><span class="badge" style="{{ $statusColor }}">{{ str($payable->status)->title() }}</span></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary" href="{{ route('supplier-payables.show', $payable) }}">View</a>
                            @if ($payable->remaining > 0)
                                <a class="btn" href="{{ route('supplier-payables.pay', $payable) }}">Pay</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No supplier payables yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('partials.pager', ['paginator' => $payables])
@endsection
