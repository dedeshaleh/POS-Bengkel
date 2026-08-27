<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('sku_prefix', 20)->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 100)->nullable()->unique()->after('sku');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('good_receives', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('purchase_id')->constrained()->nullOnDelete();
        });

        Schema::table('good_receive_items', function (Blueprint $table) {
            $table->date('expired_date')->nullable()->after('received_qty_in_base_uom');
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('purchase_item_id')->constrained()->nullOnDelete();
            $table->date('expired_date')->nullable()->after('base_uom_buy_price');
        });

        DB::table('warehouses')->insertOrIgnore([
            'code' => 'MAIN',
            'name' => 'Gudang Utama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn('expired_date');
        });

        Schema::table('good_receive_items', function (Blueprint $table) {
            $table->dropColumn('expired_date');
        });

        Schema::table('good_receives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::dropIfExists('warehouses');

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sku_prefix');
        });
    }
};
