<?php

namespace App\Services;

use App\Models\MasterPrice;
use App\Models\PriceImportBatch;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

class PriceCatalogService
{
    public function getActivePrice(Product $product): ?MasterPrice
    {
        return $product->activePrice()->first();
    }

    public function updatePrice(Product $product, float $basePrice, CarbonInterface|string $effectiveDate, string $sourceType = 'manual', ?string $sourceReference = null): MasterPrice
    {
        $startDate = is_string($effectiveDate) ? now()->parse($effectiveDate)->toDateString() : $effectiveDate->toDateString();

        return DB::transaction(function () use ($product, $basePrice, $startDate, $sourceType, $sourceReference) {
            MasterPrice::where('product_id', $product->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->update([
                    'effective_date_end' => now()->parse($startDate)->subDay()->toDateString(),
                    'is_active' => false,
                ]);

            return MasterPrice::create([
                'product_id' => $product->id,
                'base_price' => $basePrice,
                'effective_date_start' => $startDate,
                'effective_date_end' => null,
                'is_active' => true,
                'source_type' => $sourceType,
                'source_reference' => $sourceReference,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function calculateSellingPrice(Product $product, ?float $actualPurchasePrice = null): float
    {
        $sourcePrice = $actualPurchasePrice ?? (float) ($product->activePrice?->base_price ?? 0);

        if ($product->markup_type === 'fixed') {
            return $sourcePrice + (float) $product->markup_value;
        }

        return $sourcePrice + ($sourcePrice * ((float) $product->markup_value / 100));
    }

    public function importExcel(UploadedFile $file): PriceImportBatch
    {
        $batch = PriceImportBatch::create([
            'batch_number' => 'PI-' . now()->format('YmdHis'),
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
            'status' => 'processing',
        ]);

        $rows = $this->readRows($file);
        if (empty($rows)) {
            $batch->update([
                'total_rows' => 0,
                'success_rows' => 0,
                'failed_rows' => 0,
                'status' => 'failed',
            ]);
            throw new RuntimeException('Cannot read file rows. Pastikan file berisi data dan bukan file kosong.');
        }

        $success = 0;
        $failed = 0;
        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber === 0 && isset($row[0]) && strtolower(trim((string) $row[0])) === 'sku') {
                continue;
            }

            $sku = trim((string) ($row[0] ?? ''));
            $basePrice = trim((string) ($row[1] ?? ''));
            $effectiveDate = trim((string) ($row[2] ?? '')) ?: now()->toDateString();
            $error = null;
            $product = null;

            if ($sku === '') {
                $error = 'SKU is required.';
            } else {
                $product = Product::where('sku', $sku)->first();
                if (! $product) {
                    $error = "SKU '{$sku}' tidak ditemukan di database.";
                }
            }

            if ($error === null) {
                if (! is_numeric($basePrice) || (float) $basePrice < 0) {
                    $error = 'Base price harus angka dan >= 0.';
                }
            }

            if ($error === null) {
                try {
                    $this->updatePrice($product, (float) $basePrice, $effectiveDate, 'import', $batch->batch_number);
                    $success++;
                } catch (\Throwable $exception) {
                    $error = $exception->getMessage();
                }
            }

            if ($error !== null) {
                $failed++;
            }

            $batch->lines()->create([
                'row_number' => $rowNumber,
                'sku' => $sku,
                'base_price' => is_numeric($basePrice) ? (float) $basePrice : null,
                'effective_date_start' => $effectiveDate ?: null,
                'status' => $error ? 'failed' : 'success',
                'error_message' => $error,
            ]);
        }

        $batch->update([
            'total_rows' => $success + $failed,
            'success_rows' => $success,
            'failed_rows' => $failed,
            'status' => $failed > 0 ? 'completed_with_error' : 'completed',
        ]);

        return $batch;
    }

    private function readRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'csv') {
            return $this->readCsvRows($file);
        }

        return $this->readSpreadsheetRows($file);
    }

    private function readSpreadsheetRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->getRowIterator() as $rowIterator) {
            $cellIterator = $rowIterator->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getFormattedValue();

                // Convert Excel serial date to string if it's a date-formatted cell
                if (is_numeric($cellValue)) {
                    $style = $cell->getStyle();
                    $formatCode = $style->getNumberFormat()->getFormatCode();
                    if (Date::isDateTimeFormatCode($formatCode)) {
                        try {
                            $timestamp = Date::excelToTimestamp((float) $cellValue);
                            $cellValue = date('Y-m-d', $timestamp);
                        } catch (\Throwable) {
                            // keep original value
                        }
                    }
                }

                $rowData[] = $cellValue;
            }

            // Skip completely empty rows
            $filtered = array_filter($rowData, fn ($v) => $v !== null && $v !== '');
            if (empty($filtered)) {
                continue;
            }

            $rows[] = $rowData;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Normalize column count to at least 3
        return array_map(function ($row) {
            return array_pad($row, 3, '');
        }, $rows);
    }

    private function readCsvRows(UploadedFile $file): array
    {
        $handle = @fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            if (count($line) === 1 && ($line[0] === null || $line[0] === '')) {
                continue;
            }
            $rows[] = $line;
        }

        fclose($handle);

        return array_map(function ($row) {
            return array_pad($row, 3, '');
        }, $rows);
    }
}
