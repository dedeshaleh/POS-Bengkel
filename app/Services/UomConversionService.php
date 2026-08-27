<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUomConversion;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * UOM auto-conversion service.
 *
 * Product UOM conversions are stored as:
 *   from_uom_code → to_uom_code with conversion_factor
 *   e.g. 1 BOX = 10 PCS (from=BOX, to=PCS, factor=10)
 *
 * The base UOM is stored on Product.base_uom_code. This service converts
 * any purchased/sold UOM quantity into the base UOM so inventory batches
 * (which always track base UOM) stay consistent.
 */
class UomConversionService
{
    /**
     * Convert a quantity from the given UOM to the product's base UOM.
     *
     * Resolution order:
     *  1. If $fromUomCode equals base UOM → return as-is.
     *  2. Direct conversion: from_uom = $fromUom, to_uom = base → qty * factor.
     *  3. Reverse conversion: from_uom = base, to_uom = $fromUom → qty / factor.
     *
     * @throws RuntimeException when no conversion path is found.
     */
    public function convertToBaseUom(int $productId, string $fromUomCode, float $qty): int
    {
        $product = Product::findOrFail($productId);
        $baseUom = $product->base_uom_code;

        if (strtoupper($fromUomCode) === strtoupper($baseUom)) {
            return (int) $qty;
        }

        $conversions = $product->conversions;

        // Direct: from → base
        $direct = $conversions->first(fn (ProductUomConversion $c) =>
            strtoupper($c->from_uom_code) === strtoupper($fromUomCode)
            && strtoupper($c->to_uom_code) === strtoupper($baseUom)
        );

        if ($direct) {
            return (int) round($qty * (float) $direct->conversion_factor);
        }

        // Reverse: base → from (invert factor)
        $reverse = $conversions->first(fn (ProductUomConversion $c) =>
            strtoupper($c->from_uom_code) === strtoupper($baseUom)
            && strtoupper($c->to_uom_code) === strtoupper($fromUomCode)
        );

        if ($reverse && (float) $reverse->conversion_factor > 0) {
            return (int) round($qty / (float) $reverse->conversion_factor);
        }

        throw new RuntimeException(
            "No UOM conversion found for product {$product->sku} from {$fromUomCode} to base {$baseUom}."
        );
    }

    /**
     * Get all purchasable/sellable UOM options for a product.
     * Always includes the base UOM plus all conversion source UOMs.
     *
     * @return Collection<int, array{code: string, name: string, factor_to_base: float}>
     */
    public function getAvailableUoms(int $productId): Collection
    {
        $product = Product::findOrFail($productId);
        $baseUom = $product->base_uom_code;
        $conversions = $product->conversions;

        $uoms = collect([
            ['code' => $baseUom, 'name' => $baseUom, 'factor_to_base' => 1.0],
        ]);

        foreach ($conversions as $conversion) {
            // from → to means 1 from = factor to
            // If to is base, then from → base with factor
            if (strtoupper($conversion->to_uom_code) === strtoupper($baseUom)) {
                $uoms->push([
                    'code' => $conversion->from_uom_code,
                    'name' => $conversion->from_uom_code,
                    'factor_to_base' => (float) $conversion->conversion_factor,
                ]);
            }
            // If from is base, then to → base with 1/factor
            elseif (strtoupper($conversion->from_uom_code) === strtoupper($baseUom)) {
                $factor = (float) $conversion->conversion_factor;
                if ($factor > 0) {
                    $uoms->push([
                        'code' => $conversion->to_uom_code,
                        'name' => $conversion->to_uom_code,
                        'factor_to_base' => 1.0 / $factor,
                    ]);
                }
            }
        }

        return $uoms->unique('code')->values();
    }
}
