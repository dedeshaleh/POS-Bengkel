<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'sku_prefix' => strtoupper($this->faker->lexify('???')),
            'is_active' => true,
        ];
    }
}
