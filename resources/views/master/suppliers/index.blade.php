@extends('layouts.app')

@section('title', 'Master Supplier')
@section('subtitle', 'Supplier CRUD with supplier-based PPN setting.')

@section('content')
<section class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2>Supplier List</h2>
        <a href="{{ route('master.suppliers.create') }}" class="btn">Add Supplier</a>
    </div>
    <table class="table">
        <thead><tr><th>Company</th><th>Contact</th><th>PPN</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->company_name }}</td>
                    <td>{{ $supplier->contact_person ?? '-' }}<div class="muted">{{ $supplier->phone ?? '' }}</div></td>
                    <td>{{ $supplier->is_ppn_enabled ? $supplier->ppn_percentage . '%' : 'No PPN' }}</td>
                    <td>{{ $supplier->is_active ? 'Active' : 'NonAktif' }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn secondary" href="{{ route('master.suppliers.show', $supplier) }}">View</a>
                        <a class="btn secondary" href="{{ route('master.suppliers.edit', $supplier) }}">Edit</a>
                        @if ($supplier->is_active)
                            <form method="post" action="{{ route('master.suppliers.deactivate', $supplier) }}">@csrf @method('patch')<button class="btn" style="background:#b42318">NonAktif</button></form>
                        @else
                            <form method="post" action="{{ route('master.suppliers.activate', $supplier) }}">@csrf @method('patch')<button class="btn" style="background:#16794f">Aktifkan</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No suppliers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@include('partials.pager', ['paginator' => $suppliers])
@endsection
