<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('entity_type', 20)->default('corporate')->after('tax_id_npwp');
            $table->decimal('pph21_percentage', 5, 2)->default(5)->after('entity_type');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('dpp_goods_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('dpp_services_amount', 15, 2)->default(0)->after('dpp_goods_amount');
            $table->boolean('is_government_tax_collector')->default(false)->after('withholding_tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['dpp_goods_amount', 'dpp_services_amount', 'is_government_tax_collector']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'pph21_percentage']);
        });
    }
};
