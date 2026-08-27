<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 15, 2);
            $table->date('effective_date_start');
            $table->date('effective_date_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source_type', 30)->default('manual');
            $table->string('source_reference', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('price_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 100)->unique();
            $table->string('file_name');
            $table->integer('total_rows')->default(0);
            $table->integer('success_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status', 30)->default('processing');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('price_import_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_import_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('sku', 100)->nullable();
            $table->decimal('base_price', 15, 2)->nullable();
            $table->date('effective_date_start')->nullable();
            $table->string('status', 30)->default('failed');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_import_lines');
        Schema::dropIfExists('price_import_batches');
        Schema::dropIfExists('master_prices');
    }
};
