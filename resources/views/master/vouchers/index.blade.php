@extends('layouts.app')

@section('title', 'Master Voucher')
@section('subtitle', 'Kelola voucher diskon: persentase, nominal, per item, atau per transaksi.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Voucher List</h2>
        <a href="{{ route('vouchers.create') }}" class="btn">+ Create Voucher</a>
    </div>

    @if (session('status'))
        <div class="badge" style="background:#dcfce7;color:#166534;margin-bottom:12px">{{ session('status') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Value</th>
                <th>Scope</th>
                <th>Min Trans</th>
                <th>Period</th>
                <th>Used</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vouchers as $voucher)
                <tr>
                    <td><strong>{{ $voucher->code }}</strong></td>
                    <td>{{ $voucher->name ?? '-' }}</td>
                    <td>{{ $voucher->discount_type === 'percentage' ? 'Persentase' : 'Nominal' }}</td>
                    <td>
                        @if ($voucher->discount_type === 'percentage')
                            {{ $voucher->discount_value }}%
                            @if ($voucher->max_discount_amount)
                                <div class="muted" style="font-size:11px">max Rp {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}</div>
                            @endif
                        @else
                            Rp {{ number_format($voucher->discount_value, 0, ',', '.') }}
                        @endif
                    </td>
                    <td>
                        @if ($voucher->scope_type === 'item')
                            <span class="badge" style="background:#fef3c7;color:#92400e">Item</span>
                            @if ($voucher->products->isNotEmpty())
                                <div class="muted" style="font-size:11px;margin-top:2px">
                                    {{ $voucher->products->take(2)->pluck('name')->implode(', ') }}
                                    @if ($voucher->products->count() > 2) +{{ $voucher->products->count() - 2 }} more @endif
                                </div>
                            @endif
                        @else
                            <span class="badge" style="background:#dbeafe;color:#1e40af">Transaksi</span>
                        @endif
                    </td>
                    <td>{{ $voucher->min_transaction_amount > 0 ? 'Rp ' . number_format($voucher->min_transaction_amount, 0, ',', '.') : '-' }}</td>
                    <td class="muted" style="font-size:12px">
                        {{ $voucher->valid_from?->format('d M Y') ?? '?' }} — {{ $voucher->valid_until?->format('d M Y') ?? '?' }}
                    </td>
                    <td>{{ $voucher->times_used }}/{{ $voucher->usage_limit }}</td>
                    <td>{{ $voucher->is_active ? 'Active' : 'NonAktif' }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('vouchers.edit', $voucher) }}">Edit</a>
                        <form method="post" action="{{ route('vouchers.destroy', $voucher) }}" onsubmit="return confirm('Hapus voucher {{ $voucher->code }}?')">
                            @csrf @method('delete')
                            <button class="btn" style="background:#b42318">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="muted">Belum ada voucher.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $vouchers])
@endsection
