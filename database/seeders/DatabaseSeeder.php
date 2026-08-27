<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GlobalMaster;
use App\Models\InventoryBatch;
use App\Models\MasterPrice;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // ── Clean rebuild: wipe all menus and role_permissions ──
        Menu::query()->delete();
        RolePermission::query()->delete();

        // ── Module group parents (sort 50+) ──
        $moduleRoots = [
            ['name' => 'Modul Dashboard', 'url' => '/modules/dashboard', 'icon' => 'fa-solid fa-chart-line', 'sort_order' => 50],
            ['name' => 'Modul Inventory', 'url' => '/modules/inventory', 'icon' => 'fa-solid fa-boxes-stacked', 'sort_order' => 51],
            ['name' => 'Modul Purchasing', 'url' => '/modules/purchasing', 'icon' => 'fa-solid fa-truck-field', 'sort_order' => 52],
            ['name' => 'Modul POS', 'url' => '/modules/pos', 'icon' => 'fa-solid fa-cash-register', 'sort_order' => 53],
            ['name' => 'Modul CRM & Piutang', 'url' => '/modules/crm', 'icon' => 'fa-solid fa-hand-holding-dollar', 'sort_order' => 54],
            ['name' => 'Modul Laporan', 'url' => '/modules/reporting', 'icon' => 'fa-solid fa-file-invoice-dollar', 'sort_order' => 55],
            ['name' => 'Modul Sistem', 'url' => '/modules/system', 'icon' => 'fa-solid fa-gears', 'sort_order' => 56],
        ];

        $rootMap = collect($moduleRoots)->mapWithKeys(function ($root) {
            $menu = Menu::create(array_merge($root, ['parent_id' => null]));
            return [$root['url'] => $menu];
        });

        // ── Module children (single source of truth, no duplicates) ──
        $moduleChildren = [
            '/modules/dashboard' => [
                ['Dashboard', '/', 'fa-solid fa-gauge-high', 1],
                ['Notifikasi Sistem', '/modules/dashboard/system-alerts', 'fa-solid fa-bell', 2],
            ],
            '/modules/inventory' => [
                ['Data Kategori Barang', '/modules/inventory/categories', 'fa-solid fa-tags', 1],
                ['Data Produk & Sparepart', '/master/inventory', 'fa-solid fa-screwdriver-wrench', 2],
                ['Master Harga', '/master/prices', 'fa-solid fa-tags', 3],
                ['Master Gudang', '/master/warehouses', 'fa-solid fa-warehouse', 4],
                ['Data Paket Promo (Bundling)', '/modules/inventory/bundles', 'fa-solid fa-gift', 5],
                ['Kartu Stok (Stock Ledger)', '/modules/inventory/stock-ledger', 'fa-solid fa-clipboard-list', 6],
            ],
            '/modules/purchasing' => [
                ['Transaksi Pembelian', '/purchases', 'fa-solid fa-file-circle-plus', 1],
                ['Data Supplier', '/master/suppliers', 'fa-solid fa-industry', 2],
                ['Riwayat Pembelian', '/modules/purchasing/history', 'fa-solid fa-clock-rotate-left', 3],
            ],
            '/modules/pos' => [
                ['Buka Kasir (POS)', '/modules/pos/open-cashier', 'fa-solid fa-cash-register', 1],
                ['Daftar Servis Berjalan', '/modules/pos/pending-service', 'fa-solid fa-car-side', 2],
                ['Riwayat Transaksi POS', '/modules/pos/history', 'fa-solid fa-receipt', 3],
            ],
            '/modules/crm' => [
                ['Data Pelanggan', '/modules/crm/customers', 'fa-solid fa-users', 1],
                ['Buku Piutang', '/debts', 'fa-solid fa-wallet', 2],
                ['Pembayaran Cicilan', '/modules/crm/installments', 'fa-solid fa-money-bills', 3],
            ],
            '/modules/reporting' => [
                ['Laporan Penjualan', '/modules/reporting/sales', 'fa-solid fa-chart-column', 1],
                ['Laporan Laba Rugi', '/modules/reporting/profit-loss', 'fa-solid fa-scale-balanced', 2],
                ['Laporan Pajak (PPN)', '/modules/reporting/tax', 'fa-solid fa-receipt', 3],
            ],
            '/modules/system' => [
                ['Master Global', '/modules/system/global-master', 'fa-solid fa-globe', 1],
                ['Manajemen Voucher', '/master/vouchers', 'fa-solid fa-ticket', 2],
                ['Hak Akses & Pengguna', '/modules/system/rbac', 'fa-solid fa-user-shield', 3],
                ['Pengaturan Toko', '/modules/system/store-settings', 'fa-solid fa-store', 4],
            ],
        ];

        foreach ($moduleChildren as $rootUrl => $children) {
            $parent = $rootMap->get($rootUrl);
            if (! $parent) {
                continue;
            }
            foreach ($children as [$name, $url, $icon, $sort]) {
                Menu::create([
                    'name' => $name,
                    'url' => $url,
                    'icon' => $icon,
                    'sort_order' => $sort,
                    'parent_id' => $parent->id,
                ]);
            }
        }

        // ── RBAC sub-menu: Master / User / Menu / Role under Hak Akses ──
        $rbacParent = $rootMap->get('/modules/system');
        $rbacMenu = Menu::where('url', '/modules/system/rbac')->first();

        $masterMenu = Menu::create([
            'name' => 'Master', 'url' => '/master', 'icon' => 'fa-solid fa-layer-group',
            'sort_order' => 1, 'parent_id' => $rbacMenu?->id,
        ]);
        $masterUsers = Menu::create([
            'name' => 'User', 'url' => '/master/users', 'icon' => 'fa-solid fa-users',
            'sort_order' => 2, 'parent_id' => $rbacMenu?->id,
        ]);
        $masterMenusMenu = Menu::create([
            'name' => 'Menu', 'url' => '/master/menus', 'icon' => 'fa-solid fa-sitemap',
            'sort_order' => 3, 'parent_id' => $rbacMenu?->id,
        ]);
        $masterRoles = Menu::create([
            'name' => 'Role', 'url' => '/master/roles', 'icon' => 'fa-solid fa-user-shield',
            'sort_order' => 4, 'parent_id' => $rbacMenu?->id,
        ]);

        // ── Collect all menus for permission seeding ──
        $allMenus = Menu::orderBy('sort_order')->get();

        // ── Admin: full access to everything ──
        foreach ($allMenus as $menu) {
            RolePermission::create([
                'role_id' => $adminRole->id,
                'menu_id' => $menu->id,
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }

        // ── Cashier: limited access ──
        $cashierRead = [
            '/',
            '/modules/pos/open-cashier',
            '/purchases',
            '/debts',
            '/master/vouchers',
            '/modules/dashboard',
            '/modules/dashboard/system-alerts',
            '/modules/pos',
            '/modules/pos/pending-service',
            '/modules/pos/history',
            '/modules/crm',
            '/modules/crm/customers',
            '/modules/crm/installments',
        ];
        $cashierCreate = ['/modules/pos/open-cashier', '/purchases', '/debts', '/master/vouchers'];
        $cashierUpdate = ['/modules/pos/open-cashier', '/debts', '/master/vouchers'];

        foreach ($allMenus as $menu) {
            RolePermission::create([
                'role_id' => $cashierRole->id,
                'menu_id' => $menu->id,
                'can_read' => in_array($menu->url, $cashierRead),
                'can_create' => in_array($menu->url, $cashierCreate),
                'can_update' => in_array($menu->url, $cashierUpdate),
                'can_delete' => false,
            ]);
        }

        $adminUser = User::firstOrCreate(['email' => 'admin@bengkelberkah.test'], [
            'name' => 'Bengkel Admin',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $cashierUser = User::firstOrCreate(['email' => 'user1@bengkelberkah.test'], [
            'name' => 'User 1 Cashier',
            'password' => Hash::make('password'),
            'role_id' => $cashierRole->id,
        ]);

        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        $cashierUser->roles()->syncWithoutDetaching([$cashierRole->id]);

        foreach ([
            ['UOM', 'PCS', 'Pieces'],
            ['UOM', 'BOX', 'Box'],
            ['UOM', 'LITER', 'Liter'],
            ['ITEM_TYPE', 'SPAREPART', 'Sparepart'],
            ['ITEM_TYPE', 'SERVICE', 'Service'],
            ['ITEM_TYPE', 'BUNDLE', 'Promo Bundle'],
            ['PAYMENT_METHOD', 'cash', 'Cash'],
            ['PAYMENT_METHOD', 'transfer', 'Bank Transfer'],
        ] as [$category, $code, $name]) {
            GlobalMaster::firstOrCreate(['category_code' => $category, 'code' => $code], ['name' => $name]);
        }

        AppSetting::firstOrCreate(['setting_key' => 'ppn_percentage'], [
            'setting_value' => '11',
            'description' => 'Default PPN percentage applied in POS.',
        ]);

        $sparepart = Category::firstOrCreate(['name' => 'Sparepart'], ['sku_prefix' => 'SPR']);
        $oil = Category::firstOrCreate(['name' => 'Oli & Pelumas'], ['sku_prefix' => 'OLI']);
        $sparepart->update(['sku_prefix' => $sparepart->sku_prefix ?: 'SPR']);
        $oil->update(['sku_prefix' => $oil->sku_prefix ?: 'OLI']);

        $warehouse = Warehouse::firstOrCreate(['code' => 'MAIN'], [
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);

        $supplier = Supplier::updateOrCreate(['company_name' => 'PT Sumber Suku Cadang'], [
            'contact_person' => 'Budi',
            'phone' => '081234567890',
            'email' => 'sales@sumber.test',
            'is_ppn_enabled' => true,
            'ppn_percentage' => 11,
            'is_active' => true,
        ]);

        Customer::firstOrCreate(['name' => 'Rizky Motor'], [
            'phone' => '081299988877',
            'license_plate' => 'B 1234 BB',
        ]);

        $filter = Product::firstOrCreate(['sku' => 'FLT-001'], [
            'name' => 'Filter Oli Universal',
            'barcode' => 'FLT-001',
            'category_id' => $sparepart->id,
            'item_type_code' => 'SPAREPART',
            'base_uom_code' => 'PCS',
            'markup_type' => 'percentage',
            'markup_value' => 35,
            'min_stock_level' => 5,
        ]);

        $oilProduct = Product::firstOrCreate(['sku' => 'OIL-10W40'], [
            'name' => 'Oli Mesin 10W-40',
            'barcode' => 'OIL-10W40',
            'category_id' => $oil->id,
            'item_type_code' => 'SPAREPART',
            'base_uom_code' => 'PCS',
            'markup_type' => 'percentage',
            'markup_value' => 30,
            'min_stock_level' => 8,
        ]);

        $bundle = Product::firstOrCreate(['sku' => 'PKT-SERVICE-RINGAN'], [
            'name' => 'Paket Service Ringan',
            'barcode' => 'PKT-SERVICE-RINGAN',
            'category_id' => $sparepart->id,
            'item_type_code' => 'BUNDLE',
            'base_uom_code' => 'PCS',
            'is_bundle' => true,
            'markup_type' => 'fixed',
            'markup_value' => 0,
        ]);

        BundleItem::firstOrCreate([
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $filter->id,
        ], ['qty' => 1]);

        BundleItem::firstOrCreate([
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $oilProduct->id,
        ], ['qty' => 1]);

        $supplier->products()->syncWithoutDetaching([
            $filter->id => ['supplier_sku' => 'SUP-FLT-001', 'is_active' => true],
            $oilProduct->id => ['supplier_sku' => 'SUP-OIL-10W40', 'is_active' => true],
        ]);

        foreach ([[$filter, 20, 35000], [$oilProduct, 24, 55000]] as [$product, $qty, $unitCost]) {
            if ($product->batches()->exists()) {
                continue;
            }

            $purchase = Purchase::create([
                'invoice_number' => 'SEED-' . $product->sku,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'total_amount' => $qty * $unitCost,
            ]);

            $item = PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'purchased_uom_code' => 'PCS',
                'purchased_qty' => $qty,
                'qty_in_base_uom' => $qty,
                'buy_price_per_purchased_uom' => $unitCost,
                'subtotal' => $qty * $unitCost,
            ]);

            InventoryBatch::create([
                'product_id' => $product->id,
                'purchase_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'base_uom_buy_price' => $unitCost,
                'initial_qty' => $qty,
                'current_qty' => $qty,
            ]);

            $product->update(['total_stock' => $qty]);

            MasterPrice::firstOrCreate([
                'product_id' => $product->id,
                'is_active' => true,
            ], [
                'base_price' => $unitCost,
                'effective_date_start' => now()->toDateString(),
                'source_type' => 'seed',
                'source_reference' => 'Initial seed price',
            ]);
        }

        // Sample vouchers
        $vouchers = [
            [
                'code' => 'HEMAT50K',
                'name' => 'Hemat Rp 50.000',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'scope_type' => 'transaction',
                'min_transaction_amount' => 200000,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonths(6)->toDateString(),
                'usage_limit' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'HEMAT10PERCENT',
                'name' => 'Diskon 10% Transaksi',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'scope_type' => 'transaction',
                'max_discount_amount' => 50000,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonths(3)->toDateString(),
                'usage_limit' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($vouchers as $v) {
            Voucher::updateOrCreate(['code' => $v['code']], $v);
        }
    }
}
