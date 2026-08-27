@extends('layouts.app')

@section('title', 'Edit Supplier')
@section('subtitle', 'Update supplier profile and PPN setting.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>Edit Supplier</h2>
    <form method="post" action="{{ route('master.suppliers.update', $supplier) }}">
        @method('put')
        @include('master.suppliers._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn secondary">Update Supplier</button>
            <a class="btn secondary" href="{{ route('master.suppliers.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
