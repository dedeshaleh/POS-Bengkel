@extends('layouts.app')

@section('title', 'New Warehouse Transfer')
@section('subtitle', 'Create a new warehouse transfer.')

@section('content')
<section class="panel" style="max-width:1100px">
    <h2 style="margin-top:0">New Warehouse Transfer</h2>

    @if ($errors->any())
        <div class="notice" style="border-color:#b42318;background:#fee2e2">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('warehouse-transfers.store') }}" class="grid">
        @csrf
        @include('warehouse_transfers._form', ['warehouseTransfer' => null])

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
            <a href="{{ route('warehouse-transfers.index') }}" class="btn secondary">Cancel</a>
            <button class="btn">Save Transfer</button>
        </div>
    </form>
</section>
@endsection
