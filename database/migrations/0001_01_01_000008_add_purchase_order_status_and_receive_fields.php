<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('supplier_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('received_qty')->default(0)->after('purchased_qty');
            $table->decimal('received_price_per_purchased_uom', 15, 2)->nullable()->after('buy_price_per_purchased_uom');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['received_qty', 'received_price_per_purchased_uom']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
