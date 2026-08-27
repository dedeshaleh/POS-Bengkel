@extends('layouts.app')

@section('title', 'New Service Order')
@section('subtitle', 'Create a new work order.')

@section('content')
<section class="panel" style="max-width:980px">
    <h2 style="margin-top:0">New Service Order</h2>

    @if ($errors->any())
        <div class="notice" style="border-color:#b42318;background:#fee2e2">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('service-orders.store') }}" class="grid">
        @csrf
        @include('service_orders._form', ['serviceOrder' => null])

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
            <a href="{{ route('service-orders.index') }}" class="btn secondary">Cancel</a>
            <button class="btn">Save Service Order</button>
        </div>
    </form>
</section>
@endsection
