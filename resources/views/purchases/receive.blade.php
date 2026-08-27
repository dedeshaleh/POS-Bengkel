@extends('layouts.app')

@section('title', 'Good Receive')
@section('subtitle', $purchase->invoice_number)

@section('content')
<section class="panel" style="max-width:980px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <h2 style="margin:0 0 6px">{{ $purchase->invoice_number }}</h2>
            <div class="muted">{{ $purchase->supplier?->company_name ?? 'No supplier' }} | {{ $purchase->purchase_date->format('d M Y') }}</div>
        </div>
        <span class="badge">{{ str($purchase->status)->replace('_', ' ')->title() }}</span>
    </div>

    <form method="post" action="{{ route('purchases.receive', $purchase) }}">
        @csrf
        <div class="form-grid" style="margin-bottom:16px">
            <label>Surat Jalan <input name="delivery_note_number" value="{{ old('delivery_note_number') }}" required></label>
            <label>Receive Date <input type="date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" required></label>
            <label>Warehouse
                <select name="warehouse_id" required>
                    <option value="">Select warehouse</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="full">Note <input name="note" value="{{ old('note') }}"></label>
        </div>
        <div style="overflow-x:auto">
            <table class="table" style="min-width:860px">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Ordered</th>
                        <th>Received</th>
                        <th>Remaining</th>
                        <th>Receive Qty</th>
                        <th>Expired Date</th>
                        <th>PO Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        @php $remaining = max(0, $item->purchased_qty - $item->received_qty); @endphp
                        <tr>
                            <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                            <td>{{ number_format($item->purchased_qty, 2, ',', '.') }} {{ $item->purchased_uom_code }}</td>
                            <td>{{ number_format($item->received_qty, 2, ',', '.') }} {{ $item->purchased_uom_code }}</td>
                            <td>{{ number_format($remaining, 2, ',', '.') }} {{ $item->purchased_uom_code }}</td>
                            <td>
                                <input type="number" step="1" min="0" max="{{ $remaining }}" name="receive_qty[{{ $item->id }}]" value="{{ $remaining > 0 ? $remaining : 0 }}" {{ $remaining <= 0 ? 'disabled' : '' }}>
                            </td>
                            <td><input type="date" name="expired_date[{{ $item->id }}]" {{ $remaining <= 0 ? 'disabled' : '' }}></td>
                            <td>Rp {{ number_format($item->buy_price_per_purchased_uom, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row-actions" style="margin-top:16px">
            <button class="btn">Save Good Receive</button>
            <a class="btn secondary" href="{{ route('purchases.show', $purchase) }}">Back</a>
        </div>
    </form>
</section>
@endsection
