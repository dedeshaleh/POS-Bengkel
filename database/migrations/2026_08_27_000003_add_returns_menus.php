<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find or create the Purchasing module root
        $purchasingRoot = DB::table('menus')->where('url', '/modules/purchasing')->first();
        if ($purchasingRoot) {
            DB::table('menus')->insert([
                'name' => 'Retur Pembelian',
                'url' => '/returns/purchases',
                'icon' => 'fa-solid fa-rotate-left',
                'sort_order' => 4,
                'parent_id' => $purchasingRoot->id,
                'is_progress' => false,
            ]);
        }

        // Find or create the CRM module root
        $crmRoot = DB::table('menus')->where('url', '/modules/crm')->first();
        if ($crmRoot) {
            DB::table('menus')->insert([
                'name' => 'Retur Penjualan',
                'url' => '/returns/sales',
                'icon' => 'fa-solid fa-rotate-right',
                'sort_order' => 4,
                'parent_id' => $crmRoot->id,
                'is_progress' => false,
            ]);
        }

        // Grant admin full access to new menus
        $adminRole = DB::table('roles')->where('name', 'Administrator')->first();
        if ($adminRole) {
            $newMenus = DB::table('menus')->whereIn('url', ['/returns/purchases', '/returns/sales'])->get();
            foreach ($newMenus as $menu) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $adminRole->id)
                    ->where('menu_id', $menu->id)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $adminRole->id,
                        'menu_id' => $menu->id,
                        'can_read' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $menuIds = DB::table('menus')->whereIn('url', ['/returns/purchases', '/returns/sales'])->pluck('id');
        DB::table('role_permissions')->whereIn('menu_id', $menuIds)->delete();
        DB::table('menus')->whereIn('id', $menuIds)->delete();
    }
};
