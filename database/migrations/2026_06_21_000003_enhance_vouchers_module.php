<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('code');
            $table->string('scope_type', 20)->default('transaction')->after('discount_value');
            $table->decimal('min_transaction_amount', 15, 2)->default(0)->after('scope_type');
            $table->decimal('max_discount_amount', 15, 2)->nullable()->after('min_transaction_amount');
            $table->date('valid_from')->nullable()->after('max_discount_amount');
            $table->timestamps();
        });

        Schema::create('voucher_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['voucher_id', 'product_id']);
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_applied', 15, 2)->default(0);
            $table->timestamp('used_at')->useCurrent();
            $table->unique(['voucher_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('voucher_products');
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'scope_type', 'min_transaction_amount',
                'max_discount_amount', 'valid_from',
                'created_at', 'updated_at',
            ]);
        });
    }
};
