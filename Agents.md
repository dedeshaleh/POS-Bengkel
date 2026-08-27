# Agents.md — Bengkel Berkah POS & Inventory System

## Database Architecture (Final Enterprise Version)

### Overview
Enterprise-level database schema for Workshop POS and Inventory Management System using PostgreSQL. Features: Dynamic RBAC, FIFO inventory tracking, multi-day service handling, tax integration, customer debt, Global Master Data, UOM Auto-Conversion, strict Stock Locking, and Product Bundling.

### Core Entities & Business Logic

#### 1. Global Master Data & RBAC
- **Global Masters:** Centralized table `global_masters` for references like UOM, Item Types, and Payment Methods.
- **Dynamic Menus & Permissions:** `menus` and `role_permissions` map which roles can access or CRUD specific menus.

#### 2. Products, UOM, and Bundling (Promo)
- **UOM Conversions:** Table `product_uom_conversions` dictates unpacking (e.g., 1 Box = 10 Pcs).
- **Virtual Products (Bundles):** `products.is_bundle` flag marks promo packages. Packages do NOT have physical stock.
- **Bundle Components:** `bundle_items` defines the physical items inside a package. When a package is sold, the system loops through this table to deduct the physical components.

#### 3. Purchasing & FIFO Inventory
- **FIFO Logic:** `inventory_batches` stores current and initial quantities in the product's BASE UOM, retaining the historic `buy_price` for accurate Profit/Loss calculations.
- **Supplier-Based PPN:** Purchase tax is configured per supplier. Some suppliers can be marked as non-PPN, while others store their own `ppn_percentage`.
- **Purchase Discount & Withholding Tax:** Purchase Orders can store item-level discounts, a header-level discount, and optional withholding tax such as PPh 21/22/23. Payable total is calculated from net item subtotal minus header discount, plus supplier PPN, minus withholding tax.
- **Indonesian PO Tax Split:** Purchase DPP is split between goods and services. Service DPP drives PPh 23 for corporate suppliers and configurable PPh 21 for individual suppliers, while goods PPh 22 is only applied when the buyer is marked as a Government/BUMN tax collector.
- **Supplier Product Mapping:** Purchase item lookup is restricted by `supplier_products`, so a PO can only select products that the selected supplier sells. Last purchase price is prioritized by the selected supplier and product combination.
- **Auto SKU, Barcode & QR:** Product SKU is generated from the category SKU prefix. Products can store barcode/QR values for POS scanner input and printable item labels.
- **Warehouse & Expiry Tracking:** Good Receive selects a target warehouse and can store item expired dates. Inventory batches retain warehouse and expiry metadata so stock can be monitored per warehouse and expiry batch.
- **Master Price Catalog:** `master_prices` stores active and historical base prices per product with effective dates. Bulk CSV imports close old active prices and create new active prices. PO price defaults to last supplier price, then active master price, then last inventory batch.
- **Purchase Order Status:** `purchases.status` supports `draft`, `on_order`, and `closed`. Stock is added through Good Receive; a purchase becomes `closed` only when all ordered quantities have been received.
- **Good Receive Tracking:** `purchase_items` stores ordered quantity, received quantity, ordered price, and received price. Receiving can be partial, while price is controlled from the PO.
- **Good Receive Document Log:** Every receiving process creates a `good_receives` document with a generated GR number and supplier delivery note number. Partial receives create separate GR numbers and line logs in `good_receive_items`.

#### 4. Sales (POS), Tax & Strict Stock Locking
- **Stock Lock Logic:** When `sales.status` is 'in_progress' (Held cart / vehicle still serviced), the system MUST immediately deduct the `current_qty` from `inventory_batches`.
- **Item-Level Details:** `sale_items` must capture historic `buy_price`, `base_selling_price`, discounts, and `final_selling_price`.
- **Vouchers & Sales Tax:** Handles voucher code applications and sale tax calculation. Purchase PPN is supplier-based, not global.

#### 5. Customer Debt (Accounts Receivable)
- Debt tracking allows partial payments (`debt_payments`) for services where `payment_status` is not fully paid.

### Indonesian Tax Rules for Purchase Orders

#### PPN (Value Added Tax)
- **Rate**: 11% (Current Indonesian standard rate).
- **Trigger**: Applied to DPP if Vendor is PKP (Taxable Entrepreneur).
- **Formula**: PPN = 11% * DPP
- **Note**: If Vendor is "Non-PKP", PPN is 0%.

#### PPh (Income Tax) - Purchase/Withholding Logic
- **Purchase of Goods (Barang):**
  - Vendor Corporate/Individual: No PPh deducted (0%).
  - Exception: Buyer is Government/BUMN → PPh 22 at 1.5% of DPP.
- **Purchase of Services (Jasa):**
  - Vendor Corporate (PT/CV): PPh 23 at 2% of Service DPP.
  - Vendor Individual: PPh 21 using TER or progressive rate. If no NPWP, rate +20%.

#### Calculation Logic
1. Separate DPP for Goods and Services.
2. Grand Total = DPP Total + PPN Total - PPh Withholding Total.
3. Net Payable = Grand Total. Withheld PPh is buyer's tax liability.

---

## Database Schema (PostgreSQL)

```sql
-- ==============================================================================
-- DATABASE SCHEMA: Bengkel Berkah POS (FINAL ENTERPRISE VERSION)
-- Features: RBAC, Master Data, FIFO, UOM, Stock Lock, Tax, Debt, Bundling
-- ==============================================================================

-- 1. GLOBAL MASTER & APP SETTINGS
CREATE TABLE global_masters (
    id SERIAL PRIMARY KEY,
    category_code VARCHAR(50) NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    UNIQUE (category_code, code)
);

CREATE TABLE app_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT
);

-- 2. RBAC & DYNAMIC MENUS
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE menus (
    id SERIAL PRIMARY KEY,
    parent_id INT REFERENCES menus(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    icon VARCHAR(100),
    sort_order INT DEFAULT 0
);

CREATE TABLE role_permissions (
    id SERIAL PRIMARY KEY,
    role_id INT REFERENCES roles(id) ON DELETE CASCADE,
    menu_id INT REFERENCES menus(id) ON DELETE CASCADE,
    can_read BOOLEAN DEFAULT false,
    can_create BOOLEAN DEFAULT false,
    can_update BOOLEAN DEFAULT false,
    can_delete BOOLEAN DEFAULT false,
    UNIQUE (role_id, menu_id)
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT REFERENCES roles(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. MASTER DATA
CREATE TABLE suppliers (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    tax_id_npwp VARCHAR(50),
    entity_type VARCHAR(20) DEFAULT 'corporate',
    pph21_percentage NUMERIC(5, 2) DEFAULT 5,
    bank_account_info TEXT,
    bank_name VARCHAR(100),
    bank_account_name VARCHAR(150),
    bank_account_number VARCHAR(100),
    bank_branch VARCHAR(150),
    is_ppn_enabled BOOLEAN DEFAULT false,
    ppn_percentage NUMERIC(5, 2) DEFAULT 0,
    is_active BOOLEAN DEFAULT true
);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    license_plate VARCHAR(20),
    total_debt NUMERIC(15, 2) DEFAULT 0
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sku_prefix VARCHAR(20),
    is_active BOOLEAN DEFAULT true
);

CREATE TABLE vouchers (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type VARCHAR(20) DEFAULT 'fixed',
    discount_value NUMERIC(15, 2) NOT NULL,
    valid_until DATE,
    usage_limit INT DEFAULT 1,
    times_used INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true
);

-- 4. PRODUCTS, UOM, AND BUNDLING (PROMO PAKET)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    item_type_code VARCHAR(50) NOT NULL,
    base_uom_code VARCHAR(50) NOT NULL,
    is_bundle BOOLEAN DEFAULT false,
    markup_type VARCHAR(20) DEFAULT 'percentage',
    markup_value NUMERIC(10, 2) DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    total_stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE warehouses (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_uom_conversions (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    from_uom_code VARCHAR(50) NOT NULL,
    to_uom_code VARCHAR(50) NOT NULL,
    conversion_factor NUMERIC(10, 2) NOT NULL,
    UNIQUE(product_id, from_uom_code)
);

CREATE TABLE supplier_products (
    id SERIAL PRIMARY KEY,
    supplier_id INT REFERENCES suppliers(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    supplier_sku VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(supplier_id, product_id)
);

CREATE TABLE master_prices (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    base_price NUMERIC(15, 2) NOT NULL,
    effective_date_start DATE NOT NULL,
    effective_date_end DATE,
    is_active BOOLEAN DEFAULT TRUE,
    source_type VARCHAR(30) DEFAULT 'manual',
    source_reference VARCHAR(150),
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE price_import_batches (
    id SERIAL PRIMARY KEY,
    batch_number VARCHAR(100) UNIQUE NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    total_rows INT DEFAULT 0,
    success_rows INT DEFAULT 0,
    failed_rows INT DEFAULT 0,
    status VARCHAR(30) DEFAULT 'processing',
    uploaded_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE price_import_lines (
    id SERIAL PRIMARY KEY,
    price_import_batch_id INT REFERENCES price_import_batches(id) ON DELETE CASCADE,
    row_number INT NOT NULL,
    sku VARCHAR(100),
    base_price NUMERIC(15, 2),
    effective_date_start DATE,
    status VARCHAR(30) DEFAULT 'failed',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bundle_items (
    id SERIAL PRIMARY KEY,
    bundle_product_id INT REFERENCES products(id) ON DELETE CASCADE,
    component_product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    qty INT NOT NULL,
    UNIQUE(bundle_product_id, component_product_id)
);

-- 5. PURCHASING & INVENTORY (FIFO BATCH)
CREATE TABLE purchases (
    id SERIAL PRIMARY KEY,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    supplier_id INT REFERENCES suppliers(id) ON DELETE SET NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    purchase_date DATE NOT NULL,
    total_amount NUMERIC(15, 2) NOT NULL,
    discount_amount NUMERIC(15, 2) DEFAULT 0,
    dpp_goods_amount NUMERIC(15, 2) DEFAULT 0,
    dpp_services_amount NUMERIC(15, 2) DEFAULT 0,
    ppn_percentage NUMERIC(5, 2) DEFAULT 0,
    ppn_amount NUMERIC(15, 2) DEFAULT 0,
    withholding_tax_name VARCHAR(50),
    withholding_tax_percentage NUMERIC(5, 2) DEFAULT 0,
    withholding_tax_amount NUMERIC(15, 2) DEFAULT 0,
    is_government_tax_collector BOOLEAN DEFAULT FALSE,
    grand_total NUMERIC(15, 2) DEFAULT 0,
    created_by INT REFERENCES users(id)
);

CREATE TABLE purchase_items (
    id SERIAL PRIMARY KEY,
    purchase_id INT REFERENCES purchases(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    purchased_uom_code VARCHAR(50) NOT NULL,
    purchased_qty INT NOT NULL,
    received_qty INT NOT NULL DEFAULT 0,
    qty_in_base_uom INT NOT NULL,
    buy_price_per_purchased_uom NUMERIC(15, 2) NOT NULL,
    discount_type VARCHAR(20) DEFAULT 'none',
    discount_value NUMERIC(15, 2) DEFAULT 0,
    discount_amount NUMERIC(15, 2) DEFAULT 0,
    received_price_per_purchased_uom NUMERIC(15, 2),
    subtotal NUMERIC(15, 2) NOT NULL
);

CREATE TABLE good_receives (
    id SERIAL PRIMARY KEY,
    purchase_id INT REFERENCES purchases(id) ON DELETE CASCADE,
    warehouse_id INT REFERENCES warehouses(id) ON DELETE SET NULL,
    gr_number VARCHAR(100) UNIQUE NOT NULL,
    delivery_note_number VARCHAR(100) NOT NULL,
    received_date DATE NOT NULL,
    note TEXT,
    created_by INT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE good_receive_items (
    id SERIAL PRIMARY KEY,
    good_receive_id INT REFERENCES good_receives(id) ON DELETE CASCADE,
    purchase_item_id INT REFERENCES purchase_items(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    received_qty INT NOT NULL,
    received_qty_in_base_uom INT NOT NULL,
    expired_date DATE,
    buy_price_per_purchased_uom NUMERIC(15, 2) NOT NULL,
    base_uom_buy_price NUMERIC(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_batches (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    purchase_item_id INT REFERENCES purchase_items(id) ON DELETE CASCADE,
    warehouse_id INT REFERENCES warehouses(id) ON DELETE SET NULL,
    base_uom_buy_price NUMERIC(15, 2) NOT NULL,
    expired_date DATE,
    initial_qty INT NOT NULL,
    current_qty INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. SALES (POS) & STRICT STOCK LOCKING
CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    receipt_number VARCHAR(100) UNIQUE NOT NULL,
    customer_id INT REFERENCES customers(id) ON DELETE SET NULL,
    cashier_id INT REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'in_progress',
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    subtotal_amount NUMERIC(15, 2) DEFAULT 0,
    discount_amount NUMERIC(15, 2) DEFAULT 0,
    voucher_id INT REFERENCES vouchers(id) ON DELETE SET NULL,
    tax_percentage NUMERIC(5, 2) DEFAULT 0,
    tax_amount NUMERIC(15, 2) DEFAULT 0,
    grand_total NUMERIC(15, 2) DEFAULT 0,
    payment_method VARCHAR(50),
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    inventory_batch_id INT REFERENCES inventory_batches(id),
    qty INT NOT NULL,
    buy_price NUMERIC(15, 2) NOT NULL,
    base_selling_price NUMERIC(15, 2) NOT NULL,
    discount_amount NUMERIC(15, 2) DEFAULT 0,
    final_selling_price NUMERIC(15, 2) NOT NULL,
    subtotal NUMERIC(15, 2) NOT NULL
);

-- 7. ACCOUNTS RECEIVABLE (HUTANG CUSTOMER)
CREATE TABLE customer_debts (
    id SERIAL PRIMARY KEY,
    sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
    customer_id INT REFERENCES customers(id) ON DELETE CASCADE,
    total_debt NUMERIC(15, 2) NOT NULL,
    amount_paid NUMERIC(15, 2) DEFAULT 0,
    remaining_debt NUMERIC(15, 2) NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE debt_payments (
    id SERIAL PRIMARY KEY,
    customer_debt_id INT REFERENCES customer_debts(id) ON DELETE CASCADE,
    cashier_id INT REFERENCES users(id),
    amount_paid NUMERIC(15, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT
);
```

---

## Struktur Navigasi Modular: POS "Bengkel Berkah"

### 1. 🏠 Modul Dashboard
- **Ringkasan Bisnis:** Grafik pendapatan, total transaksi hari ini, dan ringkasan laba/rugi.
- **Notifikasi Sistem:** Peringatan stok menipis (low stock alert) dan daftar piutang jatuh tempo.

### 2. 📦 Modul Inventory (Gudang)
*Fokus: Mengelola fisik barang dan ketersediaan stok.*
- **Data Kategori Barang:** Pengelompokan barang (misal: Oli, Ban, Kampas Rem).
- **Data Produk & Sparepart:** Daftar barang inti beserta pengaturan stok minimum.
- **Data Paket Promo (Bundling):** Pengaturan barang virtual/paket yang memotong komponen stok fisik.
- **Kartu Stok (Stock Ledger):** Pantauan riwayat keluar-masuk barang secara real-time.

### 3. 🛒 Modul Pembelian (Purchasing)
*Fokus: Mengelola pasokan barang masuk dari luar.*
- **Data Supplier (Pemasok):** Manajemen profil dan kontak pemasok barang.
- **Transaksi Pembelian (Restock):** Input faktur pembelian untuk menambah stok dan mencatat harga beli baru (HPP/FIFO).
- **Riwayat Pembelian:** Daftar transaksi yang sudah dilakukan ke supplier.

### 4. 💳 Modul Penjualan & Kasir (POS)
*Fokus: Operasional harian di bengkel.*
- **Buka Kasir (POS):** Halaman utama kasir dengan keranjang belanja, pencarian barcode, diskon, dan pajak.
- **Daftar Servis Berjalan (Pending/Hold):** Daftar kendaraan yang masih dikerjakan, di mana transaksi berstatus in_progress, stok sudah terkunci (stock lock), dan datanya bisa diedit kembali.
- **Riwayat Transaksi POS:** Daftar struk yang sudah berstatus selesai/terbayar.

### 5. 👥 Modul Pelanggan & Piutang (CRM & Finance)
*Fokus: Mengelola interaksi pelanggan dan arus kas tertunda.*
- **Data Pelanggan:** Database nama, kontak, dan pelat nomor kendaraan.
- **Buku Piutang (Customer Debt):** Daftar pelanggan yang melakukan perbaikan dengan status belum lunas.
- **Pembayaran Cicilan:** Pencatatan setiap kali pelanggan melakukan pembayaran sebagian (partial payment).

### 6. 📊 Modul Laporan (Reporting)
*Fokus: Analisis performa bengkel untuk pemilik.*
- **Laporan Penjualan:** Rekapitulasi penjualan per hari/bulan/tahun.
- **Laporan Laba Rugi:** Perhitungan otomatis omzet dikurangi harga beli historis (HPP).
- **Laporan Pajak (PPN):** Rekapitulasi pajak yang terkumpul.

### 7. ⚙️ Modul Sistem & Pengaturan
*Fokus: Konfigurasi tingkat lanjut oleh SuperAdmin.*
- **Master Global (Referensi):** Pengaturan tipe barang, metode pembayaran, dan satuan barang (UOM) beserta nilai konversinya.
- **Manajemen Voucher:** Pembuatan kode promo dan nominal diskon.
- **Hak Akses & Pengguna (RBAC):** Pembuatan Role (SuperAdmin, Kasir, Mekanik) dan pengaturan batasan menu CRUD (Create, Read, Update, Delete).
- **Pengaturan Toko:** Nama bengkel, alamat, dan besaran persentase PPN default.
