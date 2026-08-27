<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->decimal('labor_cost', 15, 2)->default(0)->after('total_amount');
            $table->decimal('parts_subtotal', 15, 2)->default(0)->after('labor_cost');
            $table->decimal('other_cost', 15, 2)->default(0)->after('parts_subtotal');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete()->after('other_cost');
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->string('item_type')->default('sparepart')->after('product_id');
            $table->string('item_name')->nullable()->after('item_type');
            $table->foreignId('inventory_batch_id')->nullable()->after('product_id');
            $table->decimal('subtotal', 15, 2)->default(0)->after('selling_price');

            $table->foreign('inventory_batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_batch_id']);
            $table->dropColumn(['item_type', 'item_name', 'inventory_batch_id', 'subtotal']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['labor_cost', 'parts_subtotal', 'other_cost', 'sale_id']);
        });
    }
};
