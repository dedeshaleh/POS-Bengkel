<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Menu seeding is now handled centrally by DatabaseSeeder.
    }

    public function down(): void
    {
        $menu = DB::table('menus')->where('url', '/master/vouchers')->first();
        if ($menu) {
            DB::table('role_permissions')->where('menu_id', $menu->id)->delete();
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};
