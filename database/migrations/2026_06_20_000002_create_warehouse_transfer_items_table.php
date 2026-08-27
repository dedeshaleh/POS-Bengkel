<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained('warehouse_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->integer('qty');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};
