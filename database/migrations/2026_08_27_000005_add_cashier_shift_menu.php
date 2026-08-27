<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $posRoot = DB::table('menus')->where('url', '/modules/pos')->first();
        $menuId = null;

        if ($posRoot) {
            $existing = DB::table('menus')->where('url', '/cashier-shifts')->first();
            if (! $existing) {
                $menuId = DB::table('menus')->insertGetId([
                    'name' => 'Shift Kasir',
                    'url' => '/cashier-shifts',
                    'icon' => 'fa-solid fa-cash-register',
                    'sort_order' => 5,
                    'parent_id' => $posRoot->id,
                    'is_progress' => false,
                ]);
            } else {
                $menuId = $existing->id;
            }
        }

        // Grant admin + cashier read access to cashier-shifts
        $roles = DB::table('roles')->whereIn('name', ['Administrator', 'Cashier'])->get();
        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('menu_id', $menuId)
                ->exists();
            if (! $exists && $menuId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'menu_id' => $menuId,
                    'can_read' => true,
                    'can_create' => $role->name === 'Administrator',
                    'can_update' => $role->name === 'Administrator',
                    'can_delete' => $role->name === 'Administrator',
                ]);
            }
        }
    }

    public function down(): void
    {
        $menuIds = DB::table('menus')->where('url', '/cashier-shifts')->pluck('id');
        DB::table('role_permissions')->whereIn('menu_id', $menuIds)->delete();
        DB::table('menus')->whereIn('id', $menuIds)->delete();
    }
};
