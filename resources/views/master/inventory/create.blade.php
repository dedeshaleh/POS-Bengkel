@extends('layouts.app')

@section('title', 'Add Inventory Item')
@section('subtitle', 'Create product, sparepart, service, or bundle item.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>New Inventory Item</h2>
    <form method="post" action="{{ route('master.inventory.store') }}">
        @include('master.inventory._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn">Save Item</button>
            <a class="btn secondary" href="{{ route('master.inventory.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
