@extends('layouts.app')

@section('title', 'Price History')
@section('subtitle', $product->sku . ' - ' . $product->name)

@section('content')
<section class="panel" style="max-width:980px">
    <h2 style="margin-top:0">Add New Price</h2>
    <form method="post" action="{{ route('master.prices.store') }}" class="form-grid">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <label>Base Price <input type="number" step="0.01" min="0" name="base_price" required></label>
        <label>Effective Start <input type="date" name="effective_date_start" value="{{ now()->toDateString() }}" required></label>
        <label class="full">Reference / Note <input name="source_reference" placeholder="Optional"></label>
        <div class="full"><button class="btn">Save New Price</button></div>
    </form>
</section>

<section class="panel" style="max-width:980px;margin-top:16px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0">{{ $product->name }}</h2>
            <div class="muted">{{ $product->category?->name }} | Markup {{ $product->markup_type === 'fixed' ? 'Rp ' . number_format($product->markup_value, 0, ',', '.') : number_format($product->markup_value, 2, ',', '.') . '%' }}</div>
        </div>
        <a class="btn secondary" href="{{ route('master.prices.index') }}">Back</a>
    </div>
    <table class="table">
        <thead><tr><th>Base Price</th><th>Estimated Selling</th><th>Start</th><th>End</th><th>Source</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($product->prices as $price)
                <tr>
                    <td>Rp {{ number_format($price->base_price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($priceService->calculateSellingPrice($product, $price->base_price), 0, ',', '.') }}</td>
                    <td>{{ $price->effective_date_start->format('d M Y') }}</td>
                    <td>{{ $price->effective_date_end?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $price->source_type }}<div class="muted">{{ $price->source_reference }}</div></td>
                    <td>{{ $price->is_active ? 'Active' : 'Closed' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No price history yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
