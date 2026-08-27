<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateUrls = [
            '/modules/dashboard/business-summary',
            '/modules/pos/open-cashier',
            '/modules/crm/debts',
        ];

        $sampleRoot = DB::table('menus')->where('url', '/sample-menu')->first();
        if ($sampleRoot) {
            $sampleChildren = DB::table('menus')->where('parent_id', $sampleRoot->id)->pluck('id')->all();
            $duplicateMenuIds = array_merge(
                DB::table('menus')->whereIn('url', $duplicateUrls)->pluck('id')->all(),
                $sampleChildren,
                [$sampleRoot->id]
            );
        } else {
            $duplicateMenuIds = DB::table('menus')->whereIn('url', $duplicateUrls)->pluck('id')->all();
        }

        if (empty($duplicateMenuIds)) {
            return;
        }

        DB::table('role_permissions')->whereIn('menu_id', $duplicateMenuIds)->delete();
        DB::table('menus')->whereIn('id', $duplicateMenuIds)->delete();
    }

    public function down(): void
    {
        // Restoring these menus is not necessary for the cleanup migration.
    }
};
