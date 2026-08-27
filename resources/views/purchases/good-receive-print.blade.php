<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Good Receive {{ $goodReceive->gr_number }}</title>
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
        .meta td:first-child { width: 120px; color: #64748b; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        .items th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; }
        .right { text-align: right; }
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
                <h1>GOOD RECEIVE</h1>
                <div class="muted">Bengkel Berkah</div>
            </div>
            <div>
                <table class="meta">
                    <tr><td>GR Number</td><td><strong>{{ $goodReceive->gr_number }}</strong></td></tr>
                    <tr><td>Receive Date</td><td>{{ $goodReceive->received_date->format('d M Y') }}</td></tr>
                    <tr><td>Surat Jalan</td><td>{{ $goodReceive->delivery_note_number }}</td></tr>
                    <tr><td>Warehouse</td><td>{{ $goodReceive->warehouse?->code }} - {{ $goodReceive->warehouse?->name }}</td></tr>
                </table>
            </div>
        </div>

        <div class="grid">
            <section class="box">
                <h2>Supplier</h2>
                <strong>{{ $goodReceive->purchase->supplier?->company_name ?? 'No supplier' }}</strong>
                <div>{{ $goodReceive->purchase->supplier?->address }}</div>
                <div class="muted">{{ $goodReceive->purchase->supplier?->phone }}</div>
            </section>
            <section class="box">
                <h2>Purchase Order</h2>
                <table class="meta">
                    <tr><td>PO Number</td><td>{{ $goodReceive->purchase->invoice_number }}</td></tr>
                    <tr><td>PO Date</td><td>{{ $goodReceive->purchase->purchase_date->format('d M Y') }}</td></tr>
                    <tr><td>Status</td><td>{{ str($goodReceive->purchase->status)->replace('_', ' ')->title() }}</td></tr>
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
                    <th class="right">Base Qty</th>
                    <th>Expired</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($goodReceive->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product?->sku }} - {{ $item->product?->name }}</td>
                        <td>{{ $item->purchaseItem?->purchased_uom_code }}</td>
                        <td class="right">{{ number_format($item->received_qty, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($item->received_qty_in_base_uom, 0, ',', '.') }}</td>
                        <td>{{ $item->expired_date?->format('d M Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($goodReceive->note)
            <section style="margin-top:16px">
                <strong>Note:</strong>
                <div>{{ $goodReceive->note }}</div>
            </section>
        @endif

        <section class="signatures">
            <div class="signature"><div>Prepared By</div><div class="line">Admin</div></div>
            <div class="signature"><div>Checked By</div><div class="line">Warehouse</div></div>
            <div class="signature"><div>Received By</div><div class="line">Supplier / Driver</div></div>
        </section>
    </main>
</body>
</html>
