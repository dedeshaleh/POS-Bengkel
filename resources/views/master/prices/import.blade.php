@extends('layouts.app')

@section('title', 'Import Master Harga')
@section('subtitle', 'Bulk update active base price from Excel.')

@section('content')
<section class="panel" style="max-width:860px">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Upload Excel</h2>
        <a class="btn secondary" href="{{ route('master.prices.import.template') }}">Download Excel Template</a>
    </div>
    <form method="post" action="{{ route('master.prices.import.store') }}" enctype="multipart/form-data" class="form-grid">
        @csrf
        <label class="full">CSV / Excel File <input type="file" name="file" accept=".csv,.xls,.xml,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></label>
        <div class="full"><button class="btn">Import Price</button></div>
    </form>
    <div style="margin-top:12px" class="muted">
        Format kolom: <code>sku</code>, <code>base_price</code>, <code>effective_date_start</code>. Download template Excel (.xlsx), atau upload file .csv / .xls / .xlsx / .xml.
    </div>
</section>

<section class="panel" style="max-width:860px;margin-top:16px">
    <h2 style="margin-top:0">Recent Imports</h2>
    <table class="table">
        <thead><tr><th>Batch</th><th>File</th><th>Status</th><th>Success</th><th>Failed</th><th>Date</th><th>Detail</th></tr></thead>
        <tbody>
            @forelse ($batches as $batch)
                <tr>
                    <td>{{ $batch->batch_number }}</td>
                    <td>{{ $batch->file_name }}</td>
                    <td>
                        @php
                            $badgeColor = match ($batch->status) {
                                'completed' => 'background:#dcfce7;color:#166534',
                                'completed_with_error' => 'background:#fef3c7;color:#92400e',
                                'failed' => 'background:#fee2e2;color:#991b1b',
                                default => 'background:#dbeafe;color:#1d4ed8',
                            };
                        @endphp
                        <span class="badge" style="{{ $badgeColor }}">{{ str($batch->status)->replace('_', ' ')->title() }}</span>
                    </td>
                    <td>{{ $batch->success_rows }}</td>
                    <td>{{ $batch->failed_rows }}</td>
                    <td>{{ $batch->created_at->format('d M Y H:i') }}</td>
                    <td><details><summary>View</summary>
                        @if ($batch->lines()->count())
                            <table class="table" style="margin-top:8px;font-size:0.85em">
                                <thead><tr><th>Row</th><th>SKU</th><th>Price</th><th>Date</th><th>Status</th><th>Error</th></tr></thead>
                                <tbody>
                                    @foreach ($batch->lines()->orderBy('row_number')->get() as $line)
                                        <tr>
                                            <td>{{ $line->row_number }}</td>
                                            <td>{{ $line->sku }}</td>
                                            <td>{{ $line->base_price !== null ? 'Rp ' . number_format($line->base_price, 0, ',', '.') : '-' }}</td>
                                            <td>{{ $line->effective_date_start?->format('d M Y') ?? '-' }}</td>
                                            <td>
                                                @if ($line->status === 'success')
                                                    <span class="badge" style="background:#dcfce7;color:#166534">Success</span>
                                                @else
                                                    <span class="badge" style="background:#fee2e2;color:#991b1b">Failed</span>
                                                @endif
                                            </td>
                                            <td>{{ $line->error_message ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <span class="muted">No detail</span>
                        @endif
                    </details></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No imports yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:12px"><a class="btn secondary" href="{{ route('master.prices.index') }}">Back</a></div>
</section>
@endsection
