@extends('layouts.app')

@section('title', 'Add Warehouse')
@section('subtitle', 'Create warehouse master data.')

@section('content')
<section class="panel" style="max-width:760px">
    <form method="post" action="{{ route('master.warehouses.store') }}">
        @include('master.warehouses._form')
        <div class="row-actions" style="margin-top:14px">
            <button class="btn">Save Warehouse</button>
            <a class="btn secondary" href="{{ route('master.warehouses.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
