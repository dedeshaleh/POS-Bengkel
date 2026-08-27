@extends('layouts.app')

@section('title', 'Riwayat Shift Kasir')
@section('subtitle', 'Daftar shift kasir dan rekonsiliasi kas.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h2>Riwayat Shift Kasir</h2>
        <a class="btn secondary" href="{{ route('cashier-shifts.status') }}">Shift Saya</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Opening</th>
                <th>Expected</th>
                <th>Counted</th>
                <th>Selisih</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shifts as $shift)
                <tr>
                    <td>{{ $shift->shift_date?->format('Y-m-d') }}</td>
                    <td>{{ $shift->user?->name }}</td>
                    <td>{{ number_format((float) $shift->opening_cash, 2) }}</td>
                    <td>{{ $shift->expected_closing_cash !== null ? number_format((float) $shift->expected_closing_cash, 2) : '-' }}</td>
                    <td>{{ $shift->counted_closing_cash !== null ? number_format((float) $shift->counted_closing_cash, 2) : '-' }}</td>
                    <td>
                        @if ($shift->cash_difference !== null)
                            @if ((float) $shift->cash_difference < 0)
                                <span style="color:#b42318">{{ number_format((float) $shift->cash_difference, 2) }}</span>
                            @elseif ((float) $shift->cash_difference > 0)
                                <span style="color:#16a34a">+{{ number_format((float) $shift->cash_difference, 2) }}</span>
                            @else
                                0.00
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($shift->status === 'open')
                            <span class="badge" style="background:#16a34a">Open</span>
                        @else
                            <span class="badge" style="background:#475569">Closed</span>
                        @endif
                    </td>
                    <td><a class="btn secondary" href="{{ route('cashier-shifts.show', $shift) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Belum ada shift.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.pager', ['paginator' => $shifts])
</section>
@endsection
