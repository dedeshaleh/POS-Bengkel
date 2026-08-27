<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('is_active')->constrained('warehouses')->nullOnDelete();
        });

        Schema::create('warehouse_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_rack_id')->nullable()->constrained('warehouse_racks')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_racks');
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
