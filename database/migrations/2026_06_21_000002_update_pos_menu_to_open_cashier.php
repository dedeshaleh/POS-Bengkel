<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')->where('url', '/pos')->update(['url' => '/modules/pos/open-cashier']);
    }

    public function down(): void
    {
        DB::table('menus')->where('url', '/modules/pos/open-cashier')->whereNull('parent_id')->update(['url' => '/pos']);
    }
};
