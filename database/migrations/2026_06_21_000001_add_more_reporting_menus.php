<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Register the additional report pages (Revenue, Stock, Outstanding) under the
     * reporting module and grant them to every role. Existing report menus get
     * re-sorted so the navigation order stays logical.
     */
    public function up(): void
    {
        $parentId = $this->ensureMenu('/modules/reporting', [
            'name' => 'Modul Laporan',
            'icon' => 'fa-solid fa-file-invoice-dollar',
            'sort_order' => 55,
            'parent_id' => null,
            'is_progress' => false,
        ]);

        $items = [
            ['Laporan Revenue', '/modules/reporting/revenue', 'fa-solid fa-chart-line', 1],
            ['Laporan Penjualan', '/modules/reporting/sales', 'fa-solid fa-chart-column', 2],
            ['Laporan Laba Rugi', '/modules/reporting/profit-loss', 'fa-solid fa-scale-balanced', 3],
            ['Laporan Stok', '/modules/reporting/stock', 'fa-solid fa-boxes-stacked', 4],
            ['Laporan Piutang', '/modules/reporting/outstanding', 'fa-solid fa-file-invoice-dollar', 5],
            ['Laporan Pajak (PPN)', '/modules/reporting/tax', 'fa-solid fa-receipt', 6],
        ];

        $menuIds = [];
        foreach ($items as [$name, $url, $icon, $sort]) {
            $menuIds[] = $this->ensureMenu($url, [
                'name' => $name,
                'icon' => $icon,
                'sort_order' => $sort,
                'parent_id' => $parentId,
                'is_progress' => false,
            ]);
        }

        foreach (DB::table('roles')->get() as $role) {
            $isAdmin = stripos($role->name, 'admin') !== false;
            foreach ($menuIds as $menuId) {
                $this->grant($role->id, $menuId, $isAdmin);
            }
        }
    }

    public function down(): void
    {
        $urls = [
            '/modules/reporting/revenue',
            '/modules/reporting/stock',
            '/modules/reporting/outstanding',
        ];

        $ids = DB::table('menus')->whereIn('url', $urls)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('menu_id', $ids)->delete();
            DB::table('menus')->whereIn('id', $ids)->delete();
        }
    }

    private function ensureMenu(string $url, array $attributes): int
    {
        $existing = DB::table('menus')->where('url', $url)->first();

        if ($existing) {
            DB::table('menus')->where('id', $existing->id)->update($attributes);

            return (int) $existing->id;
        }

        return (int) DB::table('menus')->insertGetId(array_merge(['url' => $url], $attributes));
    }

    private function grant(int $roleId, int $menuId, bool $full): void
    {
        $values = [
            'can_read' => true,
            'can_create' => $full,
            'can_update' => $full,
            'can_delete' => $full,
        ];

        $existing = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('menu_id', $menuId)
            ->first();

        if ($existing) {
            DB::table('role_permissions')->where('id', $existing->id)->update($values);

            return;
        }

        DB::table('role_permissions')->insert(array_merge([
            'role_id' => $roleId,
            'menu_id' => $menuId,
        ], $values));
    }
};
