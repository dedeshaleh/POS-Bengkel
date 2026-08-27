# TODO Audit BengkelBerkah — Hasil Pemeriksaan 2026-08-27

Daftar pekerjaan berdasarkan audit kode vs `ARCHITECTURE_DIAGRAM.md` & `ARCHITECTURE_DIAGRAM_MISSING.md`.

## Status Legenda
- [ ] Pending
- [~] In Progress
- [x] Done

---

## 🔴 Prioritas Tinggi (Keamanan & Kebenaran)

### 1. RBAC Middleware per Menu
- [x] 1.1 Buat middleware `EnsureMenuPermission` — cek `role_permissions` (can_read/can_create/can_update/can_delete) berdasarkan request path → menu URL prefix match
- [x] 1.2 Daftarkan middleware di `bootstrap/app.php` (alias `menu.permission`)
- [x] 1.3 Terapkan middleware ke route group `auth` di `routes/web.php`
- [x] 1.4 Cache flush di AccessControlController saat menu/permission/user role berubah
- [x] 1.5 Bypass route auth/dashboard (`/logout`, `/api/alerts`, `/`, `/login`)
- [ ] 1.6 Test: user cashier tidak bisa akses menu master/users walau login (pending testing infra #7)
- [ ] 1.7 Tambah menu untuk modul yang belum ada menunya: service-orders, stock-adjustments, warehouse-transfers, supplier-payables (saat ini open/allow)

### 2. Verifikasi & Fix Bug Lama
- [x] 2.1 Cek `app/Services/PriceCatalogService.php:170` — `isDateTimeFormat()` undefined (kompatibilitas PhpSpreadsheet 5.x) — **SUDAH FIX**: kode sekarang pakai `Date::isDateTimeFormatCode()` yang ada di PhpSpreadsheet 5.x (line 416)
- [x] 2.2 Cek `resources/views/master/inventory/show.blade.php` — route `[home]` / `[inventory.index]` not defined — **SUDAH FIX**: sekarang pakai `route('master.inventory.index')` & `route('master.inventory.edit')`
- [x] 2.3 Cek `resources/views/stock_adjustments/_form.blade.php` — `Unclosed '['` Blade syntax error — **SUDAH FIX**: `php artisan view:cache` berhasil tanpa error
- [x] 2.4 Jalankan `php artisan route:list` & `php artisan view:cache` untuk verifikasi tidak ada error lain — **OK**: kedua command jalan tanpa error

### 3. Extract TaxService
- [x] 3.1 Buat `app/Services/TaxService.php` — pindah logic PPN/PPh/DPP split dari `PurchaseController`
- [x] 3.2 Method: `splitDpp()`, `calculatePpn()`, `calculateWithholdingTax()`, `calculatePurchaseTax()` (wrapper)
- [x] 3.3 Refactor `PurchaseController::store()` & `update()` pakai `TaxService` — hapus method `calculateIndonesianWithholdingTax()` dari controller
- [x] 3.4 Verifikasi: `php artisan route:list` jalan tanpa error, logic perhitungan sama (dipindah persis)

---

## 🟡 Prioritas Sedang (Fitur)

### 4. UOM Auto-Conversion Aktif
- [x] 4.1 Buat `app/Services/UomConversionService.php` — method `convertToBaseUom()` & `getAvailableUoms()`
- [x] 4.2 Integrasi PO: `PurchaseController::store()` & `update()` auto-calculate `qty_in_base_uom` dari `purchased_uom_code` + `purchased_qty` (backward compatible — jika `qty_in_base_uom` diisi manual, pakai nilai tersebut)
- [x] 4.3 Integrasi POS: `PosModuleController::saveDraft()` terima field optional `uom_code[]` — konversi qty ke base UOM sebelum FIFO lock (backward compatible — jika tidak dikirim, default base UOM)
- [x] 4.4 Endpoint `GET /modules/pos/lookup-uoms/{product}` untuk UI dapatkan list UOM per product (base + conversions)
- [ ] 4.5 UI PO: tampilkan auto-calc qty_in_base_uom saat user pilih UOM (frontend JS — pending UI update)
- [ ] 4.6 UI POS: tambah dropdown UOM per cart line (frontend JS — pending UI update)

### 5. Modul Retur (Pembelian & Penjualan)
- [x] 5.1 Migration `purchase_returns` + `purchase_return_items`
- [x] 5.2 Migration `sales_returns` + `sales_return_items`
- [x] 5.3 Model `PurchaseReturn`, `PurchaseReturnItem`, `SalesReturn`, `SalesReturnItem`
- [x] 5.4 Controller `ReturnController` — purchase & sales returns (CRUD + approve flow)
- [x] 5.5 Service `ReturnService` — stok keluar (purchase return, FIFO decrement) & stok masuk (sales return, batch restore/create)
- [x] 5.6 View index/create/show untuk purchase & sales returns + menu + permission admin
- [x] 5.7 Migration sukses, route:list & view:cache OK

### 6. Cashier Shift / Cash Drawer
- [x] 6.1 Migration `cashier_shifts` (id, user_id, shift_date, opened_at, closed_at, opening_cash, counted_closing_cash, expected_closing_cash, cash_difference, status, note)
- [x] 6.2 Migration: tambah `cashier_shift_id` ke `sales` (nullable, backward compatible)
- [x] 6.3 Model `CashierShift` + relasi ke `Sale` + method `expectedCash()`, `totalCashSales()`
- [x] 6.4 Controller `CashierShiftController` — open, close, status, index, show
- [x] 6.5 View: status (open/close form), index (history), show (reconciliation report)
- [x] 6.6 Link `sales.cashier_shift_id` saat `PosModuleController::saveDraft()` buat sale baru
- [x] 6.7 Tambah menu "Shift Kasir" via migration + permission admin (CRUD) & cashier (read)

---

## 🟢 Prioritas Rendah (Quality & Docs)

### 7. Testing
- [ ] 7.1 Factory: `ProductFactory`, `SupplierFactory`, `CustomerFactory`, `PurchaseFactory`, `SaleFactory`, `InventoryBatchFactory`
- [ ] 7.2 Unit test `InventoryService::lockForSale()` — FIFO order, bundle, insufficient stock
- [ ] 7.3 Unit test `TaxService::calculatePurchaseTax()` — PPN, PPh 21/22/23, DPP split
- [ ] 7.4 Feature test POS payment flow
- [ ] 7.5 Feature test Good Receive → inventory batch creation
- [ ] 7.6 Jalankan `php artisan test` semua hijau

### 8. Update Dokumentasi
- [ ] 8.1 Update `README.md` — hapus "Authentication screens are not installed yet" (sudah ada)
- [ ] 8.2 Update `ARCHITECTURE_DIAGRAM.md`:
  - [ ] 8.2.1 Tandai modul yang sudah done (Service Order, Stock Adjustment, Warehouse Transfer, Supplier Payable, Voucher, Barcode, Bulk Import, Cancel/Unhold)
  - [ ] 8.2.2 Update model count (37, bukan 28) + daftar lengkap
  - [ ] 8.2.3 Tambah endpoint: service-orders, stock-adjustments, warehouse-transfers, supplier-payables, vouchers, master-prices
  - [ ] 8.2.4 Perbaiki DB relationship diagram — `good_receives` muncul sekali, tambah `sale_items.inventory_batch_id`, `supplier_products`
  - [ ] 8.2.5 Tambah service layer: `TaxService`, `ServiceOrderService`, `StockAdjustmentService`, `SupplierPayableService`, `WarehouseTransferService`
- [ ] 8.3 Hapus / archive `ARCHITECTURE_DIAGRAM_MISSING.md` setelah digabung ke diagram utama

### 9. Deployment & Infrastruktur
- [ ] 9.1 Tambah deployment diagram (Nginx/Apache → PHP-FPM → PostgreSQL, queue worker, scheduler cron)
- [ ] 9.2 Setup queue worker untuk job berat (export laporan, import harga besar)
- [ ] 9.3 Caching: cache master data (global_masters, categories, uom) yang jarang berubah
- [ ] 9.4 File storage: export laporan PDF/Excel, label barcode PDF, bukti pembayaran

---

## 📊 Progress Summary

| Kategori | Total Item | Done | Pending |
|---|---|---|---|
| Prioritas Tinggi | 3 (12 sub) | 12 | 0 |
| Prioritas Sedang | 3 (22 sub) | 18 | 4 |
| Prioritas Rendah | 3 (14 sub) | 0 | 14 |
| **Total** | **9 (48 sub)** | **30** | **18** |

---

## Urutan Eksekusi
1. Prioritas Tinggi #1 (RBAC Middleware)
2. Prioritas Tinggi #2 (Verifikasi Bug Lama)
3. Prioritas Tinggi #3 (TaxService)
4. Prioritas Sedang #4 (UOM Auto-Conversion)
5. Prioritas Sedang #5 (Modul Retur)
6. Prioritas Sedang #6 (Cashier Shift)
7. Prioritas Rendah #7 (Testing)
8. Prioritas Rendah #8 (Update Docs)
9. Prioritas Rendah #9 (Deployment)
