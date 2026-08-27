@extends('layouts.app')

@section('title', 'Edit Warehouse Transfer')
@section('subtitle', 'Update the draft warehouse transfer.')

@section('content')
<section class="panel" style="max-width:1100px">
    <h2 style="margin-top:0">Edit Warehouse Transfer #{{ $warehouseTransfer->transfer_number }}</h2>

    @if ($errors->any())
        <div class="notice" style="border-color:#b42318;background:#fee2e2">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('warehouse-transfers.update', $warehouseTransfer) }}" class="grid">
        @csrf
        @method('put')
        @include('warehouse_transfers._form')

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
            <a href="{{ route('warehouse-transfers.show', $warehouseTransfer) }}" class="btn secondary">Cancel</a>
            <button class="btn">Update Transfer</button>
        </div>
    </form>
</section>
@endsection
