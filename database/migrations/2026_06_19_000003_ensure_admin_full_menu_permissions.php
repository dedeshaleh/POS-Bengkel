<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');

        if (! $adminRoleId) {
            return;
        }

        $menuIds = DB::table('menus')->pluck('id');
        $records = $menuIds->map(fn ($menuId) => [
            'role_id' => $adminRoleId,
            'menu_id' => $menuId,
            'can_read' => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ])->all();

        DB::table('role_permissions')->upsert(
            $records,
            ['role_id', 'menu_id'],
            ['can_read', 'can_create', 'can_update', 'can_delete']
        );
    }

    public function down(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');

        if (! $adminRoleId) {
            return;
        }

        DB::table('role_permissions')->where('role_id', $adminRoleId)->delete();
    }
};
