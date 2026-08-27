<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menus = [
            [
                'name' => 'Supplier Payables',
                'url' => '/supplier-payables',
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'sort_order' => 7,
            ],
            [
                'name' => 'Stock Adjustments',
                'url' => '/stock-adjustments',
                'icon' => 'fa-solid fa-clipboard-check',
                'sort_order' => 8,
            ],
        ];

        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');
        $cashierRoleId = DB::table('roles')->where('name', 'Cashier')->value('id');

        foreach ($menus as $menu) {
            $menuId = DB::table('menus')->insertGetId($menu + [
                'parent_id' => null,
            ]);

            foreach (array_filter([$adminRoleId, $cashierRoleId]) as $roleId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                    'can_read' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => false,
                ]);
            }
        }
    }

    public function down(): void
    {
        $urls = ['/supplier-payables', '/stock-adjustments'];
        $menus = DB::table('menus')->whereIn('url', $urls)->get();
        foreach ($menus as $menu) {
            DB::table('role_permissions')->where('menu_id', $menu->id)->delete();
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};
