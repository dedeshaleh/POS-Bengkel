@extends('layouts.app')

@section('title', 'Create Voucher')
@section('subtitle', 'Buat voucher diskon dengan scope transaksi atau item tertentu.')

@section('content')
<section class="panel" style="max-width:860px">
    <h2>New Voucher</h2>

    @if ($errors->any())
        <div class="badge" style="background:#fee2e2;color:#991b1b;margin-bottom:12px">Please check the form data.</div>
    @endif

    <form method="post" action="{{ route('vouchers.store') }}">
        @csrf
        @include('master.vouchers._form')
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn">Save Voucher</button>
            <a class="btn secondary" href="{{ route('vouchers.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
