<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_ppn_enabled')->default(false)->after('bank_account_info');
            $table->decimal('ppn_percentage', 5, 2)->default(0)->after('is_ppn_enabled');
            $table->boolean('is_active')->default(true)->after('ppn_percentage');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('ppn_percentage', 5, 2)->default(0)->after('total_amount');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_percentage');
            $table->decimal('grand_total', 15, 2)->default(0)->after('ppn_amount');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('total_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['ppn_percentage', 'ppn_amount', 'grand_total']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['is_ppn_enabled', 'ppn_percentage', 'is_active']);
        });
    }
};
