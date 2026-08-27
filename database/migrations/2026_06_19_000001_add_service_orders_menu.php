<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menuId = DB::table('menus')->insertGetId([
            'name' => 'Service Orders',
            'url' => '/service-orders',
            'icon' => 'fa-solid fa-wrench',
            'sort_order' => 6,
            'parent_id' => null,
        ]);

        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');
        $cashierRoleId = DB::table('roles')->where('name', 'Cashier')->value('id');

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

    public function down(): void
    {
        $menu = DB::table('menus')->where('url', '/service-orders')->first();
        if ($menu) {
            DB::table('role_permissions')->where('menu_id', $menu->id)->delete();
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};
