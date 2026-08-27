<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleRootIds = [
            'pos' => DB::table('menus')->where('url', '/modules/pos')->value('id'),
            'purchasing' => DB::table('menus')->where('url', '/modules/purchasing')->value('id'),
            'inventory' => DB::table('menus')->where('url', '/modules/inventory')->value('id'),
        ];

        $menuParents = [
            '/service-orders' => $moduleRootIds['pos'],
            '/supplier-payables' => $moduleRootIds['purchasing'],
            '/stock-adjustments' => $moduleRootIds['inventory'],
            '/warehouse-transfers' => $moduleRootIds['inventory'],
        ];

        foreach ($menuParents as $url => $parentId) {
            if (! $parentId) {
                continue;
            }

            DB::table('menus')->where('url', $url)->update([
                'parent_id' => $parentId,
            ]);
        }

        // Ensure cashier can read the module root menus so children are visible.
        $cashierRoleId = DB::table('roles')->where('name', 'Cashier')->value('id');
        if ($cashierRoleId) {
            foreach (array_filter($moduleRootIds) as $parentId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $cashierRoleId, 'menu_id' => $parentId],
                    ['can_read' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false]
                );
            }
        }
    }

    public function down(): void
    {
        $urls = ['/service-orders', '/supplier-payables', '/stock-adjustments', '/warehouse-transfers'];
        DB::table('menus')->whereIn('url', $urls)->update(['parent_id' => null]);
    }
};
