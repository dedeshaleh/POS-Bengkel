<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductUomConversion;
use App\Services\UomConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UomConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private UomConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UomConversionService::class);
    }

    public function test_convert_returns_same_qty_when_uom_equals_base(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);

        $result = $this->service->convertToBaseUom($product->id, 'PCS', 10);

        $this->assertSame(10, $result);
    }

    public function test_convert_direct_factor_box_to_pcs(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);
        ProductUomConversion::create([
            'product_id' => $product->id,
            'from_uom_code' => 'BOX',
            'to_uom_code' => 'PCS',
            'conversion_factor' => 10,
        ]);

        $result = $this->service->convertToBaseUom($product->id, 'BOX', 3);

        $this->assertSame(30, $result);
    }

    public function test_convert_reverse_factor_pcs_to_box(): void
    {
        // base = BOX, conversion stored as BOX->PCS (1 BOX = 10 PCS)
        // So converting PCS to BOX should divide by 10
        $product = Product::factory()->create(['base_uom_code' => 'BOX']);
        ProductUomConversion::create([
            'product_id' => $product->id,
            'from_uom_code' => 'BOX',
            'to_uom_code' => 'PCS',
            'conversion_factor' => 10,
        ]);

        $result = $this->service->convertToBaseUom($product->id, 'PCS', 25);

        $this->assertSame(3, $result); // 25 / 10 = 2.5 -> round to 3
    }

    public function test_convert_throws_when_no_conversion_found(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No UOM conversion found');

        $this->service->convertToBaseUom($product->id, 'UNKNOWN', 5);
    }

    public function test_get_available_uoms_includes_base_and_conversions(): void
    {
        $product = Product::factory()->create(['base_uom_code' => 'PCS']);
        ProductUomConversion::create([
            'product_id' => $product->id,
            'from_uom_code' => 'BOX',
            'to_uom_code' => 'PCS',
            'conversion_factor' => 10,
        ]);

        $uoms = $this->service->getAvailableUoms($product->id);

        $this->assertCount(2, $uoms);
        $codes = $uoms->pluck('code')->toArray();
        $this->assertContains('PCS', $codes);
        $this->assertContains('BOX', $codes);
    }
}
