<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->lexify('???-####')),
            'barcode' => $this->faker->unique()->ean13,
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'item_type_code' => 'GOODS',
            'base_uom_code' => 'PCS',
            'is_bundle' => false,
            'markup_type' => 'percentage',
            'markup_value' => 20,
            'min_stock_level' => 5,
            'total_stock' => 0,
            'is_active' => true,
        ];
    }
}
