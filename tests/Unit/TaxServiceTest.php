<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxService::class);
    }

    public function test_ppn_zero_for_non_pkp_supplier(): void
    {
        $supplier = Supplier::factory()->make(['is_ppn_enabled' => false, 'ppn_percentage' => 11]);
        $result = $this->service->calculatePpn($supplier, 100000);

        $this->assertEquals(0, $result['percentage']);
        $this->assertEquals(0, $result['amount']);
    }

    public function test_ppn_11_percent_for_pkp_supplier(): void
    {
        $supplier = Supplier::factory()->make(['is_ppn_enabled' => true, 'ppn_percentage' => 11]);
        $result = $this->service->calculatePpn($supplier, 100000);

        $this->assertSame(11.0, $result['percentage']);
        $this->assertSame(11000.0, $result['amount']);
    }

    public function test_pph22_only_when_government_collector(): void
    {
        $supplier = Supplier::factory()->make(['entity_type' => 'corporate']);
        $result = $this->service->calculateWithholdingTax($supplier, 100000, 0, true);

        $this->assertSame('PPh 22 1.5%', $result['name']);
        $this->assertSame(1.5, $result['percentage']);
        $this->assertSame(1500.0, $result['amount']);
    }

    public function test_pph22_skipped_when_not_government_collector(): void
    {
        $supplier = Supplier::factory()->make(['entity_type' => 'corporate']);
        $result = $this->service->calculateWithholdingTax($supplier, 100000, 0, false);

        $this->assertNull($result['name']);
        $this->assertEquals(0, $result['amount']);
    }

    public function test_pph23_for_corporate_services(): void
    {
        $supplier = Supplier::factory()->make(['entity_type' => 'corporate']);
        $result = $this->service->calculateWithholdingTax($supplier, 0, 50000, false);

        $this->assertSame('PPh 23 2%', $result['name']);
        $this->assertSame(1000.0, $result['amount']);
    }

    public function test_pph21_for_individual_with_npwp(): void
    {
        $supplier = Supplier::factory()->make([
            'entity_type' => 'individual',
            'pph21_percentage' => 5,
            'tax_id_npwp' => '01.234.567.8-9.000',
        ]);
        $result = $this->service->calculateWithholdingTax($supplier, 0, 50000, false);

        $this->assertStringContainsString('PPh 21', $result['name']);
        $this->assertSame(2500.0, $result['amount']); // 5% of 50000
    }

    public function test_pph21_for_individual_without_npwp_increased_20_percent(): void
    {
        $supplier = Supplier::factory()->make([
            'entity_type' => 'individual',
            'pph21_percentage' => 5,
            'tax_id_npwp' => null,
        ]);
        $result = $this->service->calculateWithholdingTax($supplier, 0, 50000, false);

        // 5% * 1.2 = 6%
        $this->assertSame(3000.0, $result['amount']); // 6% of 50000
    }

    public function test_grand_total_calculation(): void
    {
        // Subtotal 100000, discount 0, PKP 11%, no PPh
        $supplier = Supplier::factory()->make([
            'is_ppn_enabled' => true,
            'ppn_percentage' => 11,
            'entity_type' => 'corporate',
        ]);

        $lineTotals = collect([['subtotal' => 100000.0]]);
        $products = collect([1 => new Product(['item_type_code' => 'GOODS'])]);
        $productIds = [1];

        $result = $this->service->calculatePurchaseTax($supplier, $lineTotals, $products, $productIds, 0, false);

        $this->assertSame(100000.0, $result['subtotal']);
        $this->assertSame(11000.0, $result['ppn_amount']);
        $this->assertSame(111000.0, $result['grand_total']);
    }
}
