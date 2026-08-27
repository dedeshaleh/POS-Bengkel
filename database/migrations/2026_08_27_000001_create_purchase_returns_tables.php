<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('return_number', 100)->unique();
            $table->string('supplier_note_number')->nullable();
            $table->date('return_date');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, approved
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->references('id')->on('inventory_batches')->nullOnDelete();
            $table->integer('qty');
            $table->decimal('buy_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
