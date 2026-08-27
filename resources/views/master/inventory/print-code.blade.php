<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print QR {{ $product->sku }}</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f8fafc; color:#111827; }
        .actions { width:min(420px,100%); margin:18px auto; display:flex; justify-content:flex-end; gap:8px; }
        .btn { border:0; border-radius:8px; padding:10px 14px; background:#f97316; color:#fff; font-weight:700; cursor:pointer; text-decoration:none; }
        .btn.secondary { background:#334155; }
        .label { width: 340px; min-height: 430px; margin: 0 auto 18px; background:#fff; border:1px solid #d1d5db; padding:18px; display:grid; justify-items:center; align-content:start; gap:10px; }
        .qr { width:220px; height:220px; border:1px solid #e5e7eb; }
        .sku { font-size:22px; font-weight:800; }
        .name { text-align:center; font-size:15px; }
        .code { font-family: Consolas, monospace; font-size:14px; border-top:1px solid #e5e7eb; padding-top:10px; width:100%; text-align:center; }
        @media print {
            body { background:#fff; }
            .actions { display:none; }
            .label { border:1px solid #111827; margin:0; page-break-after:always; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">Print</button>
        <a class="btn secondary" href="{{ route('master.inventory.show', $product) }}">Back</a>
    </div>
    <section class="label">
        @php $code = $product->barcode ?: $product->sku; @endphp
        <img class="qr" alt="QR {{ $code }}" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($code) }}">
        <div class="sku">{{ $product->sku }}</div>
        <div class="name">{{ $product->name }}</div>
        <div class="code">{{ $code }}</div>
    </section>
</body>
</html>
