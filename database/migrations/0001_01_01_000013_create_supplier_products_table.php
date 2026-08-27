<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['supplier_id', 'product_id']);
        });

        DB::statement("
            INSERT INTO supplier_products (supplier_id, product_id, is_active, created_at, updated_at)
            SELECT DISTINCT p.supplier_id, pi.product_id, TRUE, NOW(), NOW()
            FROM purchases p
            INNER JOIN purchase_items pi ON pi.purchase_id = p.id
            WHERE p.supplier_id IS NOT NULL
            ON CONFLICT (supplier_id, product_id) DO NOTHING
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
