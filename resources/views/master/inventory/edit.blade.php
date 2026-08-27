@extends('layouts.app')

@section('title', 'Edit Inventory Item')
@section('subtitle', 'Update inventory master data.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>Edit Inventory Item</h2>
    <form method="post" action="{{ route('master.inventory.update', $product) }}">
        @method('put')
        @include('master.inventory._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn secondary">Update Item</button>
            <a class="btn secondary" href="{{ route('master.inventory.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
