@extends('layouts.app')

@section('title', 'New Stock Adjustment')
@section('subtitle', 'Create a new stock opname adjustment.')

@section('content')
<section class="panel" style="max-width:100%">
    <h2 style="margin-top:0">New Stock Adjustment</h2>

    @if ($errors->any())
        <div class="notice" style="border-color:#b42318;background:#fee2e2">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('stock-adjustments.store') }}" class="grid">
        @csrf
        @include('stock_adjustments._form', ['stockAdjustment' => null])

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
            <a href="{{ route('stock-adjustments.index') }}" class="btn secondary">Cancel</a>
            <button class="btn">Save Adjustment</button>
        </div>
    </form>
</section>
@endsection
