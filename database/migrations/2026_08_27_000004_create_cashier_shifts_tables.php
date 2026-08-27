<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('shift_date');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('counted_closing_cash', 15, 2)->nullable();
            $table->decimal('expected_closing_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->string('status', 20)->default('open'); // open, closed
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Link sales to a shift (nullable for backward compatibility)
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')->nullable()->after('cashier_id')
                ->references('id')->on('cashier_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropColumn('cashier_shift_id');
        });
        Schema::dropIfExists('cashier_shifts');
    }
};
