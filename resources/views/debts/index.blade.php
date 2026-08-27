@extends('layouts.app')

@section('title', 'Customer Debts')
@section('subtitle', 'Track receivables and partial payments.')

@section('content')
<section class="panel">
    <table class="table">
        <thead><tr><th>Customer</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Due</th><th>Payment</th></tr></thead>
        <tbody>
            @forelse ($debts as $debt)
                <tr>
                    <td>{{ $debt->customer->name }}</td>
                    <td>Rp {{ number_format($debt->total_debt, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($debt->amount_paid, 0, ',', '.') }}</td>
                    <td><span class="{{ $debt->remaining_debt > 0 ? 'danger' : '' }}">Rp {{ number_format($debt->remaining_debt, 0, ',', '.') }}</span></td>
                    <td>{{ $debt->due_date->format('d M Y') }}</td>
                    <td>
                        @if ($debt->remaining_debt > 0)
                            <form method="post" action="{{ route('debts.pay', $debt) }}" class="row-actions">
                                @csrf
                                <input type="number" step="0.01" min="1" name="amount_paid" placeholder="Amount" required>
                                <input name="payment_method" placeholder="Method">
                                <button class="btn secondary">Pay</button>
                            </form>
                        @else
                            <span class="badge">paid</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No customer debts yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $debts])
@endsection
