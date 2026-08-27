<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('good_receives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('gr_number', 100)->unique();
            $table->string('delivery_note_number', 100);
            $table->date('received_date');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('good_receive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('good_receive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('received_qty');
            $table->integer('received_qty_in_base_uom');
            $table->decimal('buy_price_per_purchased_uom', 15, 2);
            $table->decimal('base_uom_buy_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('good_receive_items');
        Schema::dropIfExists('good_receives');
    }
};
