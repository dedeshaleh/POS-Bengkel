# Struktur Navigasi Modular: POS "Bengkel Berkah"

## 1. 🏠 Modul Dashboard
- **Ringkasan Bisnis:** Grafik pendapatan, total transaksi hari ini, dan ringkasan laba/rugi.
- **Notifikasi Sistem:** Peringatan stok menipis (low stock alert) dan daftar piutang jatuh tempo.

## 2. 📦 Modul Inventory (Gudang)
*Fokus: Mengelola fisik barang dan ketersediaan stok.*
- **Data Kategori Barang:** Pengelompokan barang (misal: Oli, Ban, Kampas Rem).
- **Data Produk & Sparepart:** Daftar barang inti beserta pengaturan stok minimum.
- **Data Paket Promo (Bundling):** Pengaturan barang virtual/paket yang memotong komponen stok fisik.
- **Kartu Stok (Stock Ledger):** Pantauan riwayat keluar-masuk barang secara real-time.

## 3. 🛒 Modul Pembelian (Purchasing)
*Fokus: Mengelola pasokan barang masuk dari luar.*
- **Data Supplier (Pemasok):** Manajemen profil dan kontak pemasok barang.
- **Transaksi Pembelian (Restock):** Input faktur pembelian untuk menambah stok dan mencatat harga beli baru (HPP/FIFO).
- **Riwayat Pembelian:** Daftar transaksi yang sudah dilakukan ke supplier.

## 4. 💳 Modul Penjualan & Kasir (POS)
*Fokus: Operasional harian di bengkel.*
- **Buka Kasir (POS):** Halaman utama kasir dengan keranjang belanja, pencarian barcode, diskon, dan pajak.
- **Daftar Servis Berjalan (Pending/Hold):** Daftar kendaraan yang masih dikerjakan, di mana transaksi berstatus in_progress, stok sudah terkunci (stock lock), dan datanya bisa diedit kembali.
- **Riwayat Transaksi POS:** Daftar struk yang sudah berstatus selesai/terbayar.

## 5. 👥 Modul Pelanggan & Piutang (CRM & Finance)
*Fokus: Mengelola interaksi pelanggan dan arus kas tertunda.*
- **Data Pelanggan:** Database nama, kontak, dan pelat nomor kendaraan.
- **Buku Piutang (Customer Debt):** Daftar pelanggan yang melakukan perbaikan dengan status belum lunas.
- **Pembayaran Cicilan:** Pencatatan setiap kali pelanggan melakukan pembayaran sebagian (partial payment).

## 6. 📊 Modul Laporan (Reporting)
*Fokus: Analisis performa bengkel untuk pemilik.*
- **Laporan Penjualan:** Rekapitulasi penjualan per hari/bulan/tahun.
- **Laporan Laba Rugi:** Perhitungan otomatis omzet dikurangi harga beli historis (HPP).
- **Laporan Pajak (PPN):** Rekapitulasi pajak yang terkumpul.

## 7. ⚙️ Modul Sistem & Pengaturan
*Fokus: Konfigurasi tingkat lanjut oleh SuperAdmin.*
- **Master Global (Referensi):** Pengaturan tipe barang, metode pembayaran, dan satuan barang (UOM) beserta nilai konversinya.
- **Manajemen Voucher:** Pembuatan kode promo dan nominal diskon.
- **Hak Akses & Pengguna (RBAC):** Pembuatan Role (SuperAdmin, Kasir, Mekanik) dan pengaturan batasan menu CRUD (Create, Read, Update, Delete).
- **Pengaturan Toko:** Nama bengkel, alamat, dan besaran persentase PPN default.
