<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'purchase_item_id' => null,
            'warehouse_id' => null,
            'base_uom_buy_price' => $this->faker->randomFloat(2, 1000, 50000),
            'expired_date' => null,
            'initial_qty' => 100,
            'current_qty' => 100,
            'created_at' => now(),
        ];
    }
}
