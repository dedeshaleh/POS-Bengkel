<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MasterPrice;
use App\Models\PriceImportBatch;
use App\Models\Product;
use App\Services\PriceCatalogService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterPriceController extends Controller
{
    public function index(Request $request, PriceCatalogService $prices)
    {
        $query = Product::with(['category', 'activePrice'])
            ->where('is_active', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('sku', 'ilike', $term)
                        ->orWhere('name', 'ilike', $term)
                        ->orWhere('barcode', 'ilike', $term);
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->filled('item_type_code'), fn ($query) => $query->where('item_type_code', $request->item_type_code))
            ->orderBy('sku');

        return view('master.prices.index', [
            'products' => $query->paginate(10)->withQueryString(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'priceService' => $prices,
        ]);
    }

    public function history(Product $product, PriceCatalogService $prices)
    {
        $product->load(['category', 'prices' => fn ($query) => $query->latest('effective_date_start')->latest('id')]);

        return view('master.prices.history', [
            'product' => $product,
            'priceService' => $prices,
        ]);
    }

    public function store(Request $request, PriceCatalogService $prices)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'effective_date_start' => ['required', 'date'],
            'source_reference' => ['nullable', 'max:150'],
        ]);

        $prices->updatePrice(
            Product::findOrFail($data['product_id']),
            (float) $data['base_price'],
            $data['effective_date_start'],
            'manual',
            $data['source_reference'] ?? null
        );

        return redirect()->route('master.prices.history', $data['product_id'])->with('status', 'Master price updated.');
    }

    public function importForm()
    {
        return view('master.prices.import', [
            'batches' => PriceImportBatch::latest()->limit(10)->get(),
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'sku');
        $sheet->setCellValue('B1', 'base_price');
        $sheet->setCellValue('C1', 'effective_date_start');
        $sheet->setCellValue('A2', 'SPR-0001');
        $sheet->setCellValue('B2', '55000');
        $sheet->setCellValue('C2', now()->toDateString());
        $sheet->setCellValue('A3', 'OLI-0001');
        $sheet->setCellValue('B3', '85000');
        $sheet->setCellValue('C3', now()->toDateString());

        $path = tempnam(sys_get_temp_dir(), 'price-template-') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'master-price-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function import(Request $request, PriceCatalogService $prices)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        abort_unless(in_array(strtolower($data['file']->getClientOriginalExtension()), ['csv', 'xls', 'xlsx', 'xml'], true), 422, 'Upload harus berupa file CSV (.csv) atau Excel (.xls / .xlsx / .xml).');

        $batch = $prices->importExcel($data['file']);

        return redirect()->route('master.prices.import')->with('status', "Import {$batch->batch_number} done. Success: {$batch->success_rows}, Failed: {$batch->failed_rows}.");
    }

}
