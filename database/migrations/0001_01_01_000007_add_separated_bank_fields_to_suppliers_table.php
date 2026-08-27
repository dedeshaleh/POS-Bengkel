<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('bank_account_info');
            $table->string('bank_account_name', 150)->nullable()->after('bank_name');
            $table->string('bank_account_number', 100)->nullable()->after('bank_account_name');
            $table->string('bank_branch', 150)->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_branch',
            ]);
        });
    }
};
