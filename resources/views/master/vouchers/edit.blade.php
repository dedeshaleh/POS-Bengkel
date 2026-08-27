@extends('layouts.app')

@section('title', 'Edit Voucher')
@section('subtitle', 'Ubah voucher diskon.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>Edit Voucher — {{ $voucher->code }}</h2>

    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    <form method="post" action="{{ route('vouchers.update', $voucher) }}">
        @csrf @method('put')
        @include('master.vouchers._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn">Update Voucher</button>
            <a class="btn secondary" href="{{ route('vouchers.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
