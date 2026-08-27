# Architecture Diagram Gap Analysis — Bengkel Berkah POS

File ini merangkum hal-hal yang belum tercakup dalam `ARCHITECTURE_DIAGRAM.md` berdasarkan perbandingan dengan requirement (`database_architecture_final.md`, `AGENTS.md`) dan struktur kode yang ada.

## Progress Tracker

| Modul | Status | Route / Tabel | Catatan |
|---|---|---|---|
| **Service Orders / Work Order** | **Done** | `/service-orders` — `service_orders`, `service_order_items` | CRUD + index + menu + admin/cashier permission |
| **Supplier Payables / Hutang Supplier** | **Done** | `/supplier-payables` — `supplier_payables`, `supplier_payments` | CRUD + pembayaran + menu + admin/cashier permission |
| **Stock Adjustments / Stok Opname** | **Done** | `/stock-adjustments` — `stock_adjustments`, `stock_adjustment_items` | CRUD + finalize + update `inventory_batches.current_qty` + menu + admin/cashier permission |
| **Warehouse Transfer / Mutasi Gudang** | **Done** | `/warehouse-transfers` — `warehouse_transfers`, `warehouse_transfer_items` | CRUD + finalize + FIFO batch move + menu + admin/cashier permission |
| **Retur Pembelian / Penjualan** | Pending | — | Retur ke supplier / dari customer |
| **Manajemen Kasir / Shift** | Pending | — | Open/close shift, saldo awal, setoran |
| **UOM Auto-Conversion Flow** | Pending | — | Konversi satuan di POS/Purchase |
| **Voucher Application Flow** | Pending | — | Penerapan kode voucher di POS |
| **Debt Payment Flow** | Pending | — | `customer_debts` → `debt_payments` (endpoint sudah ada, flow diagram kurang) |
| **Cancel / Unhold Transaction Flow** | Pending | — | Mengembalikan stok saat cart dibatalkan |
| **Barcode/QR & Print Label Flow** | Pending | — | Generate & cetak label produk |
| **Bulk Price Import Flow** | Pending | — | CSV/Excel import via `price_import_batches` |

## 1. Modul Bisnis yang Belum Digambarkan

Requirement menyebutkan fitur *“multi-day service handling”* dan peran **Mekanik**, tetapi modul servis/job card tidak muncul dalam diagram. Modul yang sebaiknya ditambahkan (status lihat Progress Tracker di atas):

## 2. Alur Data yang Belum Tercakup

`ARCHITECTURE_DIAGRAM.md` memiliki beberapa *Data Flow*, namun masih kurang untuk:

- **UOM Auto-Conversion** — konversi satuan saat pembelian/penjualan (e.g., 1 Box → 10 Pcs).
- **Penerapan Voucher di POS** — bagaimana kode voucher mengurangi grand total.
- **Pembayaran Piutang (Debt Payment)** — flow dari `customer_debts` → `debt_payments`.
- **Pembatalan / Unhold Transaksi** — mekanisme mengembalikan stok dari `inventory_batches` saat cart dibatalkan.
- **Barcode/QR Generation & Print Label** — alur generate dan cetak label produk.
- **Import Harga Massal (CSV/Excel)** — `price_import_batches` dan `price_import_lines` tidak muncul di layer model maupun flow.

## 3. Ketidaksesuaian dengan Kode Aktual

Beberapa ketidaksesuaian antara diagram dan kode yang sebenarnya:

- **Layer Model tidak lengkap** — `ARCHITECTURE_DIAGRAM.md` menyebutkan 28 model, tetapi di kotak model tidak semua terekspos: `PriceImportBatch`, `PriceImportLine`, `SupplierProduct`, `Voucher`, `AppSetting`, `WarehouseRack`, `ProductUomConversion`.
- **Relasi DB diagram masih kurang tepat** — `good_receives` muncul dua kali; relasi `inventory_batches` → `sale_items` (via `sale_items.inventory_batch_id`) tidak ditampilkan; `supplier_products` tidak tergambar.
- **Service Layer terlalu ringkas** — hanya `InventoryService` dan `PriceCatalogService` yang disebutkan, padahal logika kompleks seperti tax calculation, PO/GR, POS payment sebaiknya punya service tersendiri.

## 4. Aspek Teknis & Infrastruktur yang Kurang

Diagram berfokus pada layered app, tetapi belum menyentuh:

- **Deployment Architecture** — web server, reverse proxy, queue worker, scheduler cron.
- **Queue / Background Jobs** — Laravel jobs table sudah ada di migrasi.
- **Caching Layer** — cache table migrasi ada, tapi belum digambarkan.
- **File Storage** — penyimpanan export laporan, label barcode, bukti pembayaran.
- **Logging, Monitoring & Error Handling**.
- **Testing Strategy** — unit/feature tests, factory/seeder.
- **Authentication Flow** — `README.md` menyebutkan *“Authentication screens are not installed yet”*, tapi diagram tidak menandai ini sebagai gap.

## 5. Ringkasan Endpoint yang Perlu Dilengkapi

Bagian *API Endpoints Summary* hanya mencakup contoh sebagian. Endpoint yang sudah ada tapi belum tercatat di diagram:

- `/service-orders` (index, create, store, show, edit, update)
- `/supplier-payables` + `/supplier-payables/{payable}/pay`
- `/stock-adjustments` + `/stock-adjustments/{stockAdjustment}/finalize`
- `/warehouse-transfers` + `/warehouse-transfers/{warehouseTransfer}/finalize`

Endpoint yang masih perlu ditambahkan ke diagram:

- Purchases & Good Receives
- Master Prices / Price Import
- Vouchers
- Inventory lookups
- Retur (purchase/sales returns)
- Cashier shift / open-close

## 6. Rekomendasi

1. **Perkaya `ARCHITECTURE_DIAGRAM.md`** dengan menambahkan modul bisnis poin 1 dan alur data poin 2.
2. **Selaraskan model layer** dengan daftar model yang sebenarnya di `app/Models/`.
3. **Tambahkan deployment diagram** minimal: Load → App Server → PostgreSQL → Queue Worker.
4. **Tambahkan service layer** untuk tax, purchase, POS, dan stock movement.
5. **Tandai gap autentikasi** sebagai known limitation sampai login UI selesai.

---

> Catatan: File ini bukan perubahan kode, melainkan dokumentasi gap untuk perbaikan `ARCHITECTURE_DIAGRAM.md`.
