# Module Voucher — Design Specification

## Overview

Modul voucher untuk Bengkel Berkah POS yang mendukung:
- **Discount type**: Persentase (%) dan Nominal (Rp)
- **Scope item**: Voucher bisa dibatasi hanya berlaku untuk produk tertentu
- **Scope transaksi**: Voucher bisa memotong total transaksi (header-level) atau hanya item tertentu (line-level)

---

## 1. Database Schema Changes

### 1.1 Alter `vouchers` table (tambah kolom baru)

```sql
ALTER TABLE vouchers
    ADD COLUMN name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN scope_type VARCHAR(20) DEFAULT 'transaction',
    ADD COLUMN min_transaction_amount NUMERIC(15, 2) DEFAULT 0,
    ADD COLUMN max_discount_amount NUMERIC(15, 2) DEFAULT NULL,
    ADD COLUMN valid_from DATE DEFAULT NULL,
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
```

**Field penjelasan:**

| Kolom | Tipe | Deskripsi |
|---|---|---|
| `name` | VARCHAR(150) | Nama voucher untuk display (cth: "Diskon Oli 10%") |
| `discount_type` | VARCHAR(20) | `percentage` atau `fixed` (sudah ada) |
| `discount_value` | NUMERIC(15,2) | Nilai diskon: persentase (cth 10 = 10%) atau nominal (cth 50000 = Rp 50.000) |
| `scope_type` | VARCHAR(20) | `transaction` = potong total transaksi, `item` = potong item tertentu saja |
| `min_transaction_amount` | NUMERIC(15,2) | Minimum belanja agar voucher berlaku (0 = tidak ada minimum) |
| `max_discount_amount` | NUMERIC(15,2) | Cap maksimum diskon (untuk persentase, cth: max Rp 100.000) |
| `valid_from` | DATE | Tanggal mulai berlaku |
| `valid_until` | DATE | Tanggal berakhir (sudah ada) |
| `usage_limit` | INT | Maksimum pemakaian (sudah ada) |
| `times_used` | INT | Jumlah sudah dipakai (sudah ada) |
| `is_active` | BOOLEAN | Status aktif (sudah ada) |

### 1.2 New table: `voucher_products` (untuk scope item)

```sql
CREATE TABLE voucher_products (
    id SERIAL PRIMARY KEY,
    voucher_id INT NOT NULL REFERENCES vouchers(id) ON DELETE CASCADE,
    product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (voucher_id, product_id)
);
```

**Fungsi:**
- Jika `vouchers.scope_type = 'item'`, hanya produk yang terdaftar di tabel ini yang dapat di-discount.
- Jika `vouchers.scope_type = 'transaction'`, tabel ini tidak digunakan (voucher memotong total transaksi).

### 1.3 New table: `voucher_usages` (tracking pemakaian)

```sql
CREATE TABLE voucher_usages (
    id SERIAL PRIMARY KEY,
    voucher_id INT NOT NULL REFERENCES vouchers(id) ON DELETE CASCADE,
    sale_id INT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    customer_id INT REFERENCES customers(id) ON DELETE SET NULL,
    discount_applied NUMERIC(15, 2) NOT NULL DEFAULT 0,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (voucher_id, sale_id)
);
```

**Fungsi:**
- Melacak siapa, kapan, dan berapa diskon yang diberikan per transaksi.
- Mencegah voucher dipakai 2x dalam 1 transaksi yang sama.

---

## 2. Business Logic

### 2.1 Voucher Type: `percentage`

```
discount = subtotal * (discount_value / 100)

Jika max_discount_amount IS NOT NULL:
    discount = MIN(discount, max_discount_amount)
```

**Contoh:**
- Voucher 10%, subtotal Rp 500.000 → discount = Rp 50.000
- Voucher 10%, max Rp 30.000, subtotal Rp 500.000 → discount = Rp 30.000

### 2.2 Voucher Type: `fixed`

```
discount = MIN(discount_value, subtotal)
```

**Contoh:**
- Voucher Rp 25.000, subtotal Rp 100.000 → discount = Rp 25.000
- Voucher Rp 25.000, subtotal Rp 20.000 → discount = Rp 20.000 (tidak bisa negatif)

### 2.3 Scope: `transaction` (Header-level)

Voucher memotong total transaksi setelah subtotal semua item dijumlah.

```
subtotal = SUM(line_subtotal)
discount = calculate_discount(subtotal)
grand_total = subtotal - discount + tax
```

### 2.4 Scope: `item` (Line-level)

Voucher hanya memotong item yang terdaftar di `voucher_products`.

```
eligible_subtotal = SUM(line_subtotal WHERE product_id IN voucher_products)
discount = calculate_discount(eligible_subtotal)
grand_total = subtotal - discount + tax
```

**Contoh:**
- Voucher 15% untuk produk "Oli Mesin" saja
- Cart: Oli Mesin Rp 200.000 + Spare Part Rp 300.000 = Rp 500.000
- eligible_subtotal = Rp 200.000
- discount = Rp 30.000
- grand_total = Rp 500.000 - Rp 30.000 = Rp 470.000

### 2.5 Validation Rules

Saat apply voucher di POS, sistem cek:

1. **Aktif**: `is_active = true`
2. **Periode**: `valid_from <= today <= valid_until` (jika diisi)
3. **Kuota**: `times_used < usage_limit`
4. **Minimum belanja**: `subtotal >= min_transaction_amount` (jika diisi)
5. **Scope item**: Jika `scope_type = 'item'`, minimal 1 item di cart harus ada di `voucher_products`
6. **Unik per transaksi**: Tidak ada entry di `voucher_usages` untuk `sale_id` yang sama

---

## 3. Model Relationships

```php
// Voucher.php
class Voucher extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'voucher_products');
    }

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'voucher_usages')
            ->withPivot('discount_applied', 'used_at');
    }

    // Scope: hanya voucher yang valid saat ini
    public function scopeValid($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->whereColumn('times_used', '<', 'usage_limit');
    }

    // Hitung diskon berdasarkan type & scope
    public function calculateDiscount(float $subtotal, ?array $cartProductIds = null): float
    {
        $baseAmount = $subtotal;

        if ($this->scope_type === 'item' && $cartProductIds) {
            // Hanya hitung dari item yang eligible
            $eligibleProductIds = $this->products()->pluck('products.id')->intersect($cartProductIds);
            // Caller harus pass eligible_subtotal, bukan full subtotal
            // Method ini dipanggil dengan eligible_subtotal sudah dihitung
        }

        if ($this->discount_type === 'percentage') {
            $discount = $baseAmount * ($this->discount_value / 100);
            if ($this->max_discount_amount !== null) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            $discount = min((float) $this->discount_value, $baseAmount);
        }

        return round($discount, 2);
    }
}
```

```php
// VoucherUsage.php
class VoucherUsage extends Model
{
    protected $guarded = [];
    
    public $timestamps = false;

    protected $casts = [
        'discount_applied' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

## 4. Routes

```php
// routes/web.php — di dalam middleware auth group

Route::prefix('master/vouchers')->name('vouchers.')->group(function () {
    Route::get('/', [VoucherController::class, 'index'])->name('index');
    Route::get('/create', [VoucherController::class, 'create'])->name('create');
    Route::post('/', [VoucherController::class, 'store'])->name('store');
    Route::get('/{voucher}/edit', [VoucherController::class, 'edit'])->name('edit');
    Route::put('/{voucher}', [VoucherController::class, 'update'])->name('update');
    Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
});

// API endpoint untuk apply voucher di POS
Route::post('/modules/pos/apply-voucher', [PosModuleController::class, 'applyVoucher'])
    ->name('modules.pos.apply-voucher');
```

---

## 5. Controller: VoucherController

```php
class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::withCount('usages')
            ->with('products:id,sku,name')
            ->latest()
            ->paginate(15);

        return view('master.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->where('is_bundle', false)
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        return view('master.vouchers.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'nullable|string|max:150',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'scope_type' => 'required|in:transaction,item',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $voucher = DB::transaction(function () use ($data) {
            $voucher = Voucher::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'scope_type' => $data['scope_type'],
                'min_transaction_amount' => $data['min_transaction_amount'] ?? 0,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'usage_limit' => $data['usage_limit'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($data['scope_type'] === 'item' && !empty($data['product_ids'])) {
                $voucher->products()->sync($data['product_ids']);
            }

            return $voucher;
        });

        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$voucher->code} created.");
    }

    public function edit(Voucher $voucher)
    {
        $products = Product::where('is_active', true)
            ->where('is_bundle', false)
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        $voucher->load('products:id,sku,name');

        return view('master.vouchers.edit', compact('voucher', 'products'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'name' => 'nullable|string|max:150',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'scope_type' => 'required|in:transaction,item',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::transaction(function () use ($data, $voucher) {
            $voucher->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'scope_type' => $data['scope_type'],
                'min_transaction_amount' => $data['min_transaction_amount'] ?? 0,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'usage_limit' => $data['usage_limit'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($data['scope_type'] === 'item') {
                $voucher->products()->sync($data['product_ids'] ?? []);
            } else {
                $voucher->products()->detach();
            }
        });

        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$voucher->code} updated.");
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('vouchers.index')
            ->with('status', "Voucher {$voucher->code} deleted.");
    }
}
```

---

## 6. POS Integration: Apply Voucher

### 6.1 API Endpoint di PosModuleController

```php
public function applyVoucher(Request $request)
{
    $data = $request->validate([
        'code' => 'required|string',
        'subtotal' => 'required|numeric|min:0',
        'product_ids' => 'array',
        'product_ids.*' => 'integer',
    ]);

    $voucher = Voucher::valid()
        ->where('code', strtoupper($data['code']))
        ->first();

    if (!$voucher) {
        return response()->json(['error' => 'Voucher tidak valid atau sudah kadaluarsa.'], 422);
    }

    // Cek minimum transaksi
    if ($voucher->min_transaction_amount > 0 && $data['subtotal'] < $voucher->min_transaction_amount) {
        return response()->json([
            'error' => 'Minimum belanja Rp ' . number_format($voucher->min_transaction_amount, 0, ',', '.'),
        ], 422);
    }

    // Hitung eligible subtotal berdasarkan scope
    $eligibleSubtotal = $data['subtotal'];

    if ($voucher->scope_type === 'item') {
        $eligibleProductIds = $voucher->products()->pluck('products.id')->toArray();
        $cartProductIds = $data['product_ids'] ?? [];
        $matched = array_intersect($eligibleProductIds, $cartProductIds);

        if (empty($matched)) {
            return response()->json([
                'error' => 'Voucher ini hanya berlaku untuk item tertentu yang tidak ada di cart.',
            ], 422);
        }

        // Frontend harus kirim eligible_subtotal untuk scope item
        // atau backend hitung dari sale_items (jika sale sudah ada)
        $eligibleSubtotal = (float) ($request->input('eligible_subtotal', $data['subtotal']));
    }

    $discount = $voucher->calculateDiscount($eligibleSubtotal);

    return response()->json([
        'voucher_id' => $voucher->id,
        'code' => $voucher->code,
        'name' => $voucher->name,
        'discount_type' => $voucher->discount_type,
        'scope_type' => $voucher->scope_type,
        'discount_amount' => $discount,
        'eligible_subtotal' => $eligibleSubtotal,
    ]);
}
```

### 6.2 Frontend (open-cashier.blade.php)

```javascript
// Tambahkan input voucher di area totals
// <input type="text" id="voucherCode" placeholder="Masukkan kode voucher">
// <button type="button" id="applyVoucherBtn">Apply</button>

async function applyVoucher() {
    const code = document.getElementById('voucherCode').value.trim();
    if (!code) return;

    const subtotal = parseFloat(document.getElementById('subtotalText').dataset.raw || 0);
    const productIds = [];
    rows.querySelectorAll('tr').forEach(row => {
        const pid = row.querySelector('.product-id').value;
        if (pid) productIds.push(parseInt(pid));
    });

    try {
        const res = await fetch('{{ route("modules.pos.apply-voucher") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ code, subtotal, product_ids: productIds }),
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.error || 'Gagal apply voucher');
            return;
        }

        // Update discount display
        document.getElementById('voucherDiscountText').textContent = money(data.discount_amount);
        document.getElementById('voucherId').value = data.voucher_id;
        recalcWithVoucher(data.discount_amount);
    } catch (e) {
        alert('Error applying voucher');
    }
}
```

---

## 7. UI Mockup — Master Voucher

### 7.1 Index Page

```
+---------------------------------------------------------------+
| Vouchers                                          [+ Create]  |
+---------------------------------------------------------------+
| Code       | Name           | Type  | Value | Scope  | Used  |
|------------|----------------|-------|-------|--------|-------|
| OLI10      | Diskon Oli 10% | %     | 10    | Item   | 3/10  |
| HEMAT50K   | Hemat 50K      | Fixed | 50000 | Trans  | 12/50 |
| MEMBER15   | Member 15%     | %     | 15    | Trans  | 0/100 |
+---------------------------------------------------------------+
```

### 7.2 Create/Edit Form

```
+---------------------------------------------------------------+
| Create Voucher                                                |
+---------------------------------------------------------------+
| Code:           [________]                                    |
| Name:           [________________________]                    |
| Discount Type:  ( ) Percentage  ( ) Fixed                     |
| Value:          [________]  (% atau Rp)                       |
| Scope:          ( ) Transaction  ( ) Specific Items           |
|                 ┌─ jika Item ────────────────────────┐        |
|                 │ Select Products:                    │        |
|                 │ [x] Oli Meskin 5W-30  (SKU-001)    │        |
|                 │ [ ] Filter Udara     (SKU-002)     │        |
|                 │ [x] Busi Iridium     (SKU-003)     │        |
|                 └─────────────────────────────────────┘        |
| Min Transaction:[________] (0 = no minimum)                   |
| Max Discount:   [________] (kosong = tanpa batas)             |
| Valid From:     [________]   Valid Until: [________]          |
| Usage Limit:    [________]                                    |
| Active:         [x]                                           |
|                                [Save]  [Cancel]               |
+---------------------------------------------------------------+
```

---

## 8. Migration File

```php
// database/migrations/2026_06_21_000003_enhance_vouchers_module.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('code');
            $table->string('scope_type', 20)->default('transaction')->after('discount_value');
            $table->decimal('min_transaction_amount', 15, 2)->default(0)->after('scope_type');
            $table->decimal('max_discount_amount', 15, 2)->nullable()->after('min_transaction_amount');
            $table->date('valid_from')->nullable()->after('max_discount_amount');
            $table->timestamps();
        });

        Schema::create('voucher_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['voucher_id', 'product_id']);
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_applied', 15, 2)->default(0);
            $table->timestamp('used_at')->useCurrent();
            $table->unique(['voucher_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('voucher_products');
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'scope_type', 'min_transaction_amount',
                'max_discount_amount', 'valid_from',
                'created_at', 'updated_at',
            ]);
        });
    }
};
```

---

## 9. Seeder

```php
// Tambahkan di DatabaseSeeder.php

$vouchers = [
    [
        'code' => 'HEMAT50K',
        'name' => 'Hemat Rp 50.000',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'scope_type' => 'transaction',
        'min_transaction_amount' => 200000,
        'valid_from' => '2026-06-01',
        'valid_until' => '2026-12-31',
        'usage_limit' => 100,
        'is_active' => true,
    ],
    [
        'code' => 'OLI10',
        'name' => 'Diskon Oli 10%',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'scope_type' => 'item',
        'max_discount_amount' => 30000,
        'valid_from' => '2026-06-01',
        'valid_until' => '2026-09-30',
        'usage_limit' => 50,
        'is_active' => true,
    ],
];

foreach ($vouchers as $v) {
    $voucher = Voucher::updateOrCreate(['code' => $v['code']], $v);
    // Attach products untuk scope item
    if ($v['scope_type'] === 'item') {
        $oliProducts = Product::where('name', 'ilike', '%oli%')->pluck('id');
        $voucher->products()->sync($oliProducts);
    }
}
```

---

## 10. Implementation Checklist

- [ ] Create migration `2026_06_21_000003_enhance_vouchers_module.php`
- [ ] Update `Voucher` model (relationships, scope, calculateDiscount)
- [ ] Create `VoucherUsage` model
- [ ] Create `VoucherController` (CRUD)
- [ ] Create views: `master/vouchers/index.blade.php`, `create.blade.php`, `edit.blade.php`
- [ ] Add routes for voucher CRUD
- [ ] Add `applyVoucher` endpoint in `PosModuleController`
- [ ] Add voucher input UI in `open-cashier.blade.php`
- [ ] Update `saveDraft` to store `voucher_id` and apply discount
- [ ] Add voucher menu in seeder
- [ ] Add role permissions for voucher menu
- [ ] Test: percentage voucher on transaction scope
- [ ] Test: fixed voucher on transaction scope
- [ ] Test: percentage voucher on item scope
- [ ] Test: voucher with minimum transaction
- [ ] Test: voucher with max discount cap
- [ ] Test: expired voucher rejection
- [ ] Test: usage limit enforcement
