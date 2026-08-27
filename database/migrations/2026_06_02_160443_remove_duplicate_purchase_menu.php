<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus menu placeholder duplikat "Transaksi Pembelian" - /modules/purchasing/restock
        // Karena yg real ada di /purchases
        Menu::where('url', '/modules/purchasing/restock')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore: tambah kembali jika diperlukan
        $purchasingParent = Menu::where('url', '/modules/purchasing')->first();
        if ($purchasingParent) {
            Menu::firstOrCreate(
                ['url' => '/modules/purchasing/restock'],
                [
                    'name' => 'Transaksi Pembelian',
                    'icon' => 'fa-solid fa-file-circle-plus',
                    'sort_order' => 2,
                    'parent_id' => $purchasingParent->id,
                ]
            );
        }
    }
};
