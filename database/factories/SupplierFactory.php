<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company,
            'contact_person' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->safeEmail,
            'address' => $this->faker->address,
            'tax_id_npwp' => '01.234.567.8-9.000',
            'entity_type' => 'corporate',
            'pph21_percentage' => 5,
            'is_ppn_enabled' => true,
            'ppn_percentage' => 11,
            'is_active' => true,
        ];
    }
}
