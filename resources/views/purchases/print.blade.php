<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order {{ $purchase->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, sans-serif; font-size: 13px; background: #f8fafc; }
        .page { width: min(900px, 100%); margin: 18px auto; background: #fff; padding: 28px; border: 1px solid #e5e7eb; }
        .top { display: flex; justify-content: space-between; gap: 18px; border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 18px; }
        h1 { margin: 0; font-size: 24px; letter-spacing: 0; }
        h2 { margin: 0 0 8px; font-size: 15px; }
        .muted { color: #64748b; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .box { border: 1px solid #e5e7eb; padding: 12px; min-height: 104px; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta td:first-child { width: 132px; color: #64748b; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        .items th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; }
        .right { text-align: right; }
        .totals { margin-top: 14px; display: grid; gap: 6px; justify-content: end; }
        .totals > div { display: flex; gap: 16px; }
        .totals strong { width: 160px; text-align: right; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 44px; }
        .signature { text-align: center; min-height: 110px; display: grid; align-content: space-between; }
        .line { border-top: 1px solid #111827; padding-top: 6px; }
        .print-actions { width: min(900px, 100%); margin: 18px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: #f97316; color: #fff; font-weight: 700; cursor: pointer; text-decoration: none; }
        .btn.secondary { background: #334155; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; width: 100%; border: 0; padding: 16mm; }
            .print-actions { display: none; }
        }
        @media (max-width: 700px) {
            .top, .grid, .signatures { grid-template-columns: 1fr; display: grid; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button class="btn" onclick="window.print()">Print</button>
        <a class="btn secondary" href="{{ route('purchases.show', $purchase) }}">Back</a>
    </div>

    <main class="page">
        <div class="top">
            <div>
                <h1>PURCHASE ORDER</h1>
                <div class="muted">Bengkel Berkah</div>
            </div>
            <div>
                <table class="meta">
                    <tr><td>PO Number</td><td><strong>{{ $purchase->invoice_number }}</strong></td></tr>
                    <tr><td>PO Date</td><td>{{ $purchase->purchase_date->format('d M Y') }}</td></tr>
                    <tr><td>Status</td><td>{{ str($purchase->status)->replace('_', ' ')->title() }}</td></tr>
                </table>
            </div>
        </div>

        <div class="grid">
            <section class="box">
                <h2>Supplier</h2>
                <strong>{{ $purchase->supplier?->company_name ?? 'No supplier' }}</strong>
                <div>{{ $purchase->supplier?->address }}</div>
                <div class="muted">{{ $purchase->supplier?->phone }}</div>
            </section>
            <section class="box">
                <h2>Tax Info</h2>
                <table class="meta">
                    <tr><td>PPN</td><td>{{ $purchase->supplier?->is_ppn_enabled ? 'PKP ' . $purchase->ppn_percentage . '%' : 'Non-PKP' }}</td></tr>
                    <tr><td>Entity</td><td>{{ str($purchase->supplier?->entity_type ?? 'corporate')->title() }}</td></tr>
                    <tr><td>Collector</td><td>{{ $purchase->is_government_tax_collector ? 'PPh 22' : 'Private' }}</td></tr>
                </table>
            </section>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:42px">No</th>
                    <th>Product</th>
                    <th>UOM</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th>Discount</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                        <td>{{ $item->purchased_uom_code }}</td>
                        <td class="right">{{ number_format($item->purchased_qty, 2, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->buy_price_per_purchased_uom, 0, ',', '.') }}</td>
                        <td>
                            @if (($item->discount_type ?? 'none') === 'percentage')
                                {{ number_format($item->discount_value ?? 0, 2, ',', '.') }}%
                            @elseif (($item->discount_type ?? 'none') === 'fixed')
                                Rp {{ number_format($item->discount_value ?? 0, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><strong>Subtotal:</strong> <span>Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span></div>
            @if (($purchase->discount_amount ?? 0) > 0)
                <div><strong>Discount:</strong> <span>Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span></div>
            @endif
            <div><strong>PPN ({{ number_format($purchase->ppn_percentage, 2, ',', '.') }}%):</strong> <span>Rp {{ number_format($purchase->ppn_amount, 0, ',', '.') }}</span></div>
            @if ($purchase->withholding_tax_name)
                <div><strong>{{ $purchase->withholding_tax_name }}:</strong> <span>Rp {{ number_format($purchase->withholding_tax_amount ?? 0, 0, ',', '.') }}</span></div>
            @endif
            <div><strong>Grand Total:</strong> <span>Rp {{ number_format($purchase->grand_total ?: $purchase->total_amount, 0, ',', '.') }}</span></div>
        </div>

        <section class="signatures">
            <div class="signature"><div>Prepared By</div><div class="line">Admin</div></div>
            <div class="signature"><div>Approved By</div><div class="line">Finance / Manager</div></div>
            <div class="signature"><div>Received By</div><div class="line">Supplier</div></div>
        </section>
    </main>
</body>
</html>
