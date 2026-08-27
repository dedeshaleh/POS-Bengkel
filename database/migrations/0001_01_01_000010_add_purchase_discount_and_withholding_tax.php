<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            $table->string('withholding_tax_name', 50)->nullable()->after('ppn_amount');
            $table->decimal('withholding_tax_percentage', 5, 2)->default(0)->after('withholding_tax_name');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0)->after('withholding_tax_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'discount_amount',
                'withholding_tax_name',
                'withholding_tax_percentage',
                'withholding_tax_amount',
            ]);
        });
    }
};
