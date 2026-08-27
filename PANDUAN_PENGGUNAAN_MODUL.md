# Panduan Penggunaan Modul Baru — Bengkel Berkah POS

Dokumen ini menjelaskan langkah demi langkah penggunaan modul-modul yang baru ditambahkan:

- **Service Orders / Work Order** (di dalam menu **POS**)
- **Supplier Payables / Hutang Supplier** (di dalam menu **Purchasing**)
- **Stock Adjustments / Stok Opname** (di dalam menu **Inventory**)
- **Warehouse Transfers / Mutasi Gudang** (di dalam menu **Inventory**)

> Pastikan login sebagai **Administrator** atau **Cashier** agar menu dan permission sudah aktif.

---

## 1. Service Orders / Work Order

Lokasi menu: **POS → Service Orders**

### A. Membuat Service Order baru

1. Klik tombol **Add Service Order**.
2. Isi field utama:
   - **Customer** — wajib, pilih dari daftar customer.
   - **Mechanic** — optional, pilih mekanik yang mengerjakan.
   - **Status** — pilih `Pending`, `In Progress`, `Completed`, atau `Cancelled`.
   - **Estimated Completion** — tanggal estimasi selesai (optional).
   - **Notes** — catatan umum (optional).
3. Tambahkan item sparepart / jasa:
   - Klik **Add Item**.
   - Klik tombol **Search** di kolom **Product**, ketik **SKU, nama, atau barcode**, lalu klik produk yang muncul di modal.
   - Isi **Qty** (jumlah).
   - Isi **Buy Price** (harga modal) dan **Selling Price** (harga jual).
   - Isi **Notes** jika perlu.
   - Total di bagian bawah akan terhitung otomatis dari `qty × selling_price`.
4. Klik **Save**. Sistem akan membuat nomor order `WO-YYYYMMDD-NNNN`.

### B. Mengedit Service Order

1. Dari daftar, klik **Edit** pada service order yang berstatus **Draft/Pending/In Progress**.
   - Service Order yang sudah **Completed** atau **Cancelled** tidak bisa diedit.
2. Ubah data yang diperlukan, item bisa ditambah/dihapus.
3. Klik **Save**.

### C. Melihat detail

- Klik **View** pada daftar untuk melihat customer, mechanic, status, item, dan total.

---

## 2. Supplier Payables / Hutang Supplier

Lokasi menu: **Purchasing → Supplier Payables**

### A. Membuat Hutang Supplier dari Purchase Order

1. Klik **Add Supplier Payable**.
2. Di field **Purchase**, pilih faktur pembelian yang sudah ada.
   - Setelah dipilih, **Supplier** dan **Total Amount** akan terisi otomatis dari data PO.
3. Isi **Due Date** (tanggal jatuh tempo).
4. Tambahkan **Notes** jika perlu.
5. Klik **Save Payable**.

### B. Membuat Hutang Supplier Manual

1. Klik **Add Supplier Payable**.
2. Biarkan field **Purchase** kosong (`-- No purchase --`).
3. Pilih **Supplier** secara manual.
4. Isi **Total Amount** sesuai nominal hutang.
5. Isi **Due Date**.
6. Klik **Save Payable**.

### C. Mencatat Pembayaran

1. Dari daftar, klik **View** pada payable yang ingin dibayar.
2. Klik tombol **Pay**.
3. Isi form pembayaran:
   - **Amount Paid** — nominal yang dibayar (default sisa hutang, maksimal sisa hutang).
   - **Payment Method** — contoh: Cash, Transfer, QRIS.
   - **Payment Date** — tanggal pembayaran.
   - **Note** — catatan (optional).
4. Klik **Save Payment**.
5. Sistem akan menghitung ulang **Remaining** dan mengubah status menjadi `partial` atau `paid`.

### D. Status Hutang

- **unpaid** — belum ada pembayaran.
- **partial** — sudah ada pembayaran sebagian.
- **paid** — sudah lunas.

---

## 3. Stock Adjustments / Stok Opname

Lokasi menu: **Inventory → Stock Adjustments**

Gunakan fitur ini untuk mencatat hasil stock opname / penyesuaian stok fisik dengan sistem.

### A. Membuat Stock Adjustment

1. Klik **Add Stock Adjustment**.
2. Isi field utama:
   - **Warehouse** — gudang yang diopname.
   - **Adjustment Date** — tanggal opname.
   - **Reason** — alasan penyesuaian, misal: "Opname bulanan", "Rusak", "Hilang".
   - **Notes** — catatan tambahan.
3. Tambahkan item (2 cara):

   **Cara A — Scan barcode/QR (cepat):**
   - Fokuskan kursor ke kolom **Scan Barcode / QR** di atas tabel.
   - Scan barcode atau ketik **SKU** produk, lalu tekan **Enter**.
   - Baris baru otomatis bertambah dengan produk terpilih, **Expected Qty** terisi otomatis dari stok sistem.
   - Isi **Actual Qty** (stok fisik) di baris tersebut.

   **Cara B — Manual search:**
   - Klik **Add Item**.
   - Klik tombol **Search** di kolom **Product**, ketik **SKU, nama, atau barcode**, lalu klik produk di modal.
   - Klik tombol **Search** di kolom **Batch** (optional):
     - Kosongkan (No specific batch) untuk menyesuaikan total stok produk secara umum.
     - Pilih batch tertentu untuk menyesuaikan stok batch tersebut. Batch yang ditampilkan sudah difilter berdasarkan produk dan warehouse.
   - **Expected Qty** — stok menurut sistem (akan terisi otomatis dari batch / total stok).
   - **Actual Qty** — stok hasil fisik / sebenarnya.
   - **Notes** — catatan per item.
4. Klik **Save**. Status akan menjadi **draft**.

### B. Memposting / Finalize Stock Adjustment

1. Dari daftar atau detail, klik **Finalize**.
2. Klik tombol konfirmasi finalize.
3. Sistem akan:
   - Menghitung selisih = `actual_qty - expected_qty`.
   - Mengupdate `inventory_batches.current_qty` sesuai selisih (jika batch dipilih).
   - Atau mengupdate `products.total_stock` jika tidak ada batch.
   - Mencegah stok negatif.
4. Setelah finalize, status berubah menjadi **finalized** dan tidak bisa diedit lagi.

> Catatan: Hanya adjustment berstatus **draft** yang bisa di-edit atau di-finalize.

---

## 4. Warehouse Transfers / Mutasi Gudang

Lokasi menu: **Inventory → Warehouse Transfers**

Gunakan fitur ini untuk memindahkan stok dari satu gudang ke gudang lain.

### A. Membuat Warehouse Transfer

1. Klik **Add Warehouse Transfer**.
2. Isi field utama:
   - **From Warehouse** — gudang asal.
   - **To Warehouse** — gudang tujuan (tidak boleh sama dengan asal).
   - **Transfer Date** — tanggal transfer.
   - **Notes** — catatan.
3. Tambahkan item:
   - Klik **Add Item**.
   - Klik tombol **Search** di kolom **Product**, ketik **SKU, nama, atau barcode**, lalu klik produk di modal.
   - Klik tombol **Search** di kolom **Batch** (optional):
     - Pilih `Auto (FIFO)` untuk mengambil stok dari batch tertua di gudang asal.
     - Pilih batch tertentu untuk mengambil dari batch tersebut. Batch yang ditampilkan sudah difilter berdasarkan produk dan **From Warehouse**.
   - Isi **Qty** — jumlah yang dipindahkan.
   - Isi **Notes** jika perlu.
4. Klik **Save**. Status akan menjadi **draft** dan nomor transfer `TF-YYYYMMDD-NNNN` akan dibuat.

### B. Memposting / Finalize Transfer

1. Dari daftar atau detail, klik **Finalize**.
2. Klik tombol konfirmasi finalize.
3. Sistem akan:
   - Mengurangi `current_qty` batch di gudang asal.
   - Membuat / menambah batch baru di gudang tujuan dengan harga beli dan tanggal kadaluarsa yang sama.
   - Mengupdate `products.total_stock`.
   - Mencegah stok negatif.
4. Setelah finalize, status berubah menjadi **completed** dan tidak bisa diedit lagi.

> Catatan: Hanya transfer berstatus **draft** yang bisa di-edit atau di-finalize.

---

## Catatan Umum

- Semua modul baru menggunakan **layout aplikasi yang sama** (`layouts.app`) dan gaya TailwindCSS yang konsisten dengan modul lain.
- Semua modul baru sudah terintegrasi dengan **RBAC**:
  - **Administrator** punya full CRUD permission.
  - **Cashier** punya read/create/update permission (tanpa delete).
- Menu baru sudah dimasukkan ke dalam **module induk** yang relevan, bukan sebagai menu standalone.
- Jika ada data yang tidak muncul di dropdown (customer, supplier, product, warehouse, batch), pastikan data master tersebut sudah aktif (`is_active = true`) dan sudah ada stoknya (untuk batch/product).

---

> File ini akan diupdate seiring bertambahnya modul baru.
