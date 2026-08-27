<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Indonesian tax calculation service for Purchase Orders.
 *
 * Rules (per AGENTS.md):
 *  - PPN: 11% of DPP if supplier is PKP (is_ppn_enabled). Non-PKP = 0%.
 *  - PPh 22: 1.5% of goods DPP, only when buyer is Government/BUMN collector.
 *  - PPh 23: 2% of services DPP, when supplier is corporate (PT/CV).
 *  - PPh 21: services DPP * supplier pph21_percentage (x1.2 if no NPWP),
 *    when supplier is individual.
 *
 * Grand Total = DPP (taxable) + PPN - PPh Withholding.
 */
class TaxService
{
    /**
     * Split line subtotals into goods vs services DPP, then apply header
     * discount proportionally across each bucket.
     *
     * @param  Collection<int, array>  $lineTotals  Each item: ['subtotal' => float, ...]
     * @param  Collection<int, Product>  $products  Keyed by id
     * @param  array<int, int>  $productIds  Product id per line, aligned with $lineTotals
     * @param  float  $discount  Header-level discount amount
     * @return array{goods_dpp: float, services_dpp: float, taxable: float, subtotal: float}
     */
    public function splitDpp(Collection $lineTotals, Collection $products, array $productIds, float $discount): array
    {
        $subtotal = $lineTotals->sum('subtotal');
        $discount = min($subtotal, max(0, $discount));
        $taxable = max(0, $subtotal - $discount);

        $goodsBefore = $lineTotals->sum(function (array $line, int $index) use ($productIds, $products) {
            $product = $products->get((int) $productIds[$index]);
            return strtoupper((string) $product?->item_type_code) === 'SERVICE' ? 0 : $line['subtotal'];
        });

        $servicesBefore = $lineTotals->sum(function (array $line, int $index) use ($productIds, $products) {
            $product = $products->get((int) $productIds[$index]);
            return strtoupper((string) $product?->item_type_code) === 'SERVICE' ? $line['subtotal'] : 0;
        });

        $goodsDpp = $subtotal > 0
            ? max(0, $goodsBefore - ($discount * ($goodsBefore / $subtotal)))
            : 0;
        $servicesDpp = $subtotal > 0
            ? max(0, $servicesBefore - ($discount * ($servicesBefore / $subtotal)))
            : 0;

        return [
            'subtotal' => $subtotal,
            'taxable' => $taxable,
            'goods_dpp' => $goodsDpp,
            'services_dpp' => $servicesDpp,
        ];
    }

    /**
     * Calculate PPN (Value Added Tax) based on supplier PKP status.
     *
     * @return array{percentage: float, amount: float}
     */
    public function calculatePpn(?Supplier $supplier, float $taxable): array
    {
        $percentage = $supplier?->is_ppn_enabled ? (float) $supplier->ppn_percentage : 0;

        return [
            'percentage' => $percentage,
            'amount' => $taxable * ($percentage / 100),
        ];
    }

    /**
     * Calculate Indonesian withholding tax (PPh) for goods and services.
     *
     * @return array{name: string|null, percentage: float, amount: float}
     */
    public function calculateWithholdingTax(?Supplier $supplier, float $goodsDpp, float $servicesDpp, bool $isGovernmentTaxCollector): array
    {
        $articles = [];
        $singlePercentage = 0;
        $amount = 0;

        // PPh 22 — goods, only when buyer is Government/BUMN tax collector.
        if ($goodsDpp > 0 && $isGovernmentTaxCollector) {
            $articles[] = 'PPh 22 1.5%';
            $singlePercentage = 1.5;
            $amount += $goodsDpp * 0.015;
        }

        // PPh 23 — services, corporate supplier.
        if ($servicesDpp > 0 && ($supplier?->entity_type ?? 'corporate') === 'corporate') {
            $articles[] = 'PPh 23 2%';
            $singlePercentage = 2;
            $amount += $servicesDpp * 0.02;
        }

        // PPh 21 — services, individual supplier (x1.2 if no NPWP).
        if ($servicesDpp > 0 && ($supplier?->entity_type ?? 'corporate') === 'individual') {
            $percentage = (float) ($supplier?->pph21_percentage ?? 5);
            if (blank($supplier?->tax_id_npwp)) {
                $percentage *= 1.2;
            }

            $articles[] = 'PPh 21 ' . rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.') . '%';
            $singlePercentage = $percentage;
            $amount += $servicesDpp * ($percentage / 100);
        }

        return [
            'name' => empty($articles) ? null : implode(' + ', $articles),
            'percentage' => count($articles) === 1 ? $singlePercentage : 0,
            'amount' => $amount,
        ];
    }

    /**
     * Full purchase tax calculation: DPP split + PPN + PPh + grand total.
     *
     * @return array{
     *     subtotal: float,
     *     taxable: float,
     *     discount: float,
     *     goods_dpp: float,
     *     services_dpp: float,
     *     ppn_percentage: float,
     *     ppn_amount: float,
     *     withholding_tax_name: string|null,
     *     withholding_tax_percentage: float,
     *     withholding_tax_amount: float,
     *     grand_total: float,
     * }
     */
    public function calculatePurchaseTax(?Supplier $supplier, Collection $lineTotals, Collection $products, array $productIds, float $discount, bool $isGovernmentTaxCollector): array
    {
        $dpp = $this->splitDpp($lineTotals, $products, $productIds, $discount);
        $ppn = $this->calculatePpn($supplier, $dpp['taxable']);
        $wht = $this->calculateWithholdingTax($supplier, $dpp['goods_dpp'], $dpp['services_dpp'], $isGovernmentTaxCollector);

        return [
            'subtotal' => $dpp['subtotal'],
            'taxable' => $dpp['taxable'],
            'discount' => min($dpp['subtotal'], max(0, $discount)),
            'goods_dpp' => $dpp['goods_dpp'],
            'services_dpp' => $dpp['services_dpp'],
            'ppn_percentage' => $ppn['percentage'],
            'ppn_amount' => $ppn['amount'],
            'withholding_tax_name' => $wht['name'],
            'withholding_tax_percentage' => $wht['percentage'],
            'withholding_tax_amount' => $wht['amount'],
            'grand_total' => max(0, $dpp['taxable'] + $ppn['amount'] - $wht['amount']),
        ];
    }
}
