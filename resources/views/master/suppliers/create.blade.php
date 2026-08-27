@extends('layouts.app')

@section('title', 'Add Supplier')
@section('subtitle', 'Create supplier and PPN behavior.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>New Supplier</h2>
    <form method="post" action="{{ route('master.suppliers.store') }}">
        @include('master.suppliers._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn">Save Supplier</button>
            <a class="btn secondary" href="{{ route('master.suppliers.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
