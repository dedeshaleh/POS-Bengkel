<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_masters', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 50);
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['category_code', 'code']);
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->text('description')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('url')->nullable();
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_read')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->unique(['role_id', 'menu_id']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id_npwp', 50)->nullable();
            $table->text('bank_account_info')->nullable();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->decimal('total_debt', 15, 2)->default(0);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_value', 15, 2);
            $table->date('valid_until')->nullable();
            $table->integer('usage_limit')->default(1);
            $table->integer('times_used')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type_code', 50);
            $table->string('base_uom_code', 50);
            $table->boolean('is_bundle')->default(false);
            $table->string('markup_type', 20)->default('percentage');
            $table->decimal('markup_value', 10, 2)->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->integer('total_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('from_uom_code', 50);
            $table->string('to_uom_code', 50);
            $table->decimal('conversion_factor', 10, 2);
            $table->unique(['product_id', 'from_uom_code']);
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->integer('qty');
            $table->unique(['bundle_product_id', 'component_product_id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100)->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('total_amount', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('purchased_uom_code', 50);
            $table->integer('purchased_qty');
            $table->integer('qty_in_base_uom');
            $table->decimal('buy_price_per_purchased_uom', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_uom_buy_price', 15, 2);
            $table->integer('initial_qty');
            $table->integer('current_qty');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 100)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('in_progress');
            $table->string('payment_status', 20)->default('unpaid');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->integer('qty');
            $table->decimal('buy_price', 15, 2);
            $table->decimal('base_selling_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_selling_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_debt', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('remaining_debt', 15, 2);
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_debt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('customer_debts');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('product_uom_conversions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('menus');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('role_id'));
        Schema::dropIfExists('roles');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('global_masters');
    }
};
