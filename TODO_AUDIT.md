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
- [ ] 2.1 Cek `app/Services/PriceCatalogService.php:170` — `isDateTimeFormat()` undefined (kompatibilitas PhpSpreadsheet 5.x)
- [ ] 2.2 Cek `resources/views/master/inventory/show.blade.php` — route `[home]` / `[inventory.index]` not defined
- [ ] 2.3 Cek `resources/views/stock_adjustments/_form.blade.php` — `Unclosed '['` Blade syntax error
- [ ] 2.4 Jalankan `php artisan route:list` & `php artisan view:cache` untuk verifikasi tidak ada error lain

### 3. Extract TaxService
- [ ] 3.1 Buat `app/Services/TaxService.php` — pindah logic PPN/PPh/DPP split dari `PurchaseController` (line 83-102, 330-347)
- [ ] 3.2 Method: `calculatePurchaseTax(Supplier $supplier, float $goodsDpp, float $servicesDpp, bool $isGovCollector): array`
- [ ] 3.3 Refactor `PurchaseController::store()` & `update()` pakai `TaxService`
- [ ] 3.4 Verifikasi hasil perhitungan tetap sama

---

## 🟡 Prioritas Sedang (Fitur)

### 4. UOM Auto-Conversion Aktif
- [ ] 4.1 Tambah method `convertToBaseUom(int $productId, string $fromUomCode, float $qty): int` di service baru `UomConversionService` atau di `InventoryService`
- [ ] 4.2 Pakai di `PurchaseController` — input `purchased_qty` + `purchased_uom_code` → auto hitung `qty_in_base_uom` via `conversion_factor`
- [ ] 4.3 Pakai di `PosModuleController` — tampilkan & jual dalam UOM non-base, konversi ke base saat lock stock
- [ ] 4.4 UI: pilih UOM di PO & POS (dropdown dari `product_uom_conversions`)

### 5. Modul Retur (Pembelian & Penjualan)
- [ ] 5.1 Migration `purchase_returns` + `purchase_return_items`
- [ ] 5.2 Migration `sales_returns` + `sales_return_items`
- [ ] 5.3 Model `PurchaseReturn`, `PurchaseReturnItem`, `SalesReturn`, `SalesReturnItem`
- [ ] 5.4 Controller `PurchaseReturnController` + `SalesReturnController`
- [ ] 5.5 Service: return stok ke `inventory_batches` (reverse FIFO) atau buat batch negatif
- [ ] 5.6 View CRUD + menu + permission
- [ ] 5.7 Update `ARCHITECTURE_DIAGRAM.md` — tambah flow retur

### 6. Cashier Shift / Cash Drawer
- [ ] 6.1 Migration `cashier_shifts` (open_at, close_at, opening_balance, closing_balance, expected_closing, difference, status, cashier_id)
- [ ] 6.2 Migration `cashier_settlements` (cash, card, transfer, voucher, dll)
- [ ] 6.3 Model `CashierShift` + `CashierSettlement`
- [ ] 6.4 Controller `CashierShiftController` — open, close, settlement
- [ ] 6.5 Hubungkan `sales.cashier_shift_id` (migration add column)
- [ ] 6.6 View open-shift, close-shift, settlement
- [ ] 6.7 Menu + permission

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
| Prioritas Tinggi | 3 (12 sub) | 5 | 7 |
| Prioritas Sedang | 3 (20 sub) | 0 | 20 |
| Prioritas Rendah | 3 (14 sub) | 0 | 14 |
| **Total** | **9 (46 sub)** | **5** | **41** |

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
