@extends('layouts.app')

@section('title', $moduleName)
@section('subtitle', $featureName . ' - On Progress')

@section('content')
<section class="panel" style="max-width:900px">
    <h2>{{ $moduleName }} / {{ $featureName }}</h2>
    <p class="muted">Halaman ini masih dalam tahap pengembangan. Struktur menu sudah siap sesuai rancangan modul.</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
        <span class="badge" style="background:#fef3c7;color:#92400e">On Progress</span>
        <a href="{{ route('dashboard') }}" class="btn secondary">Back to Dashboard</a>
    </div>
</section>
@endsection

