@extends('layouts.app')

@section('title', 'Retur Penjualan')
@section('subtitle', 'Daftar retur barang dari customer.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h2>Retur Penjualan</h2>
        <a class="btn" href="{{ route('returns.sales.create') }}">+ Retur Baru</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No. Retur</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($returns as $return)
                <tr>
                    <td>{{ $return->return_number }}</td>
                    <td>{{ $return->customer?->name ?? '-' }}</td>
                    <td>{{ $return->return_date?->format('Y-m-d') }}</td>
                    <td>{{ number_format((float) $return->total_amount, 2) }}</td>
                    <td>
                        @if ($return->status === 'approved')
                            <span class="badge" style="background:#16a34a">Approved</span>
                        @else
                            <span class="badge" style="background:#ca8a04">Draft</span>
                        @endif
                    </td>
                    <td><a class="btn secondary" href="{{ route('returns.sales.show', $return) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada retur penjualan.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.pager', ['paginator' => $returns])
</section>
@endsection
