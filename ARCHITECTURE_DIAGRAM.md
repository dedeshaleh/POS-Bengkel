# Bengkel Berkah POS & Inventory System - Architecture Diagram

## Technology Stack

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                            │
├─────────────────────────────────────────────────────────────┤
│  Blade Templates (TailwindCSS)  │  Vite (Asset Build)       │
│  - layouts/app.blade.php         │  - JavaScript bundling    │
│  - module-specific views         │  - CSS processing         │
│  - partials/components           │                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Laravel Framework (PHP 8.3)                                │
│  - Routing (web.php)                                        │
│  - Middleware (auth, guest, menu.permission)                │
│  - Controllers (18 modules)                                 │
│  - Services (Business Logic)                                │
│  - Models (Eloquent ORM)                                    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    DATA LAYER                               │
├─────────────────────────────────────────────────────────────┤
│  PostgreSQL Database                                        │
│  - 30+ tables with migrations                               │
│  - FIFO inventory batches                                   │
│  - RBAC system (menu.permission middleware)                 │
│  - Tax calculations (Indonesian)                           │
│  - Returns (purchase & sales)                              │
│  - Cashier shifts                                          │
└─────────────────────────────────────────────────────────────┘
```

## System Architecture Overview

```
                    ┌─────────────────────────────────────┐
                    │         USER INTERFACE               │
                    │  (Blade Templates + TailwindCSS)      │
                    └──────────────┬──────────────────────┘
                                   │ HTTP Requests
                    ┌──────────────▼──────────────────────┐
                    │         ROUTING LAYER                │
                    │  (routes/web.php)                    │
                    │  - Guest routes (login)              │
                    │  - Auth routes (protected)           │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │      CONTROLLER LAYER                │
                    │  (18 Controllers)                    │
                    │  - DashboardController               │
                    │  - InventoryController               │
                    │  - PurchaseController                │
                    │  - PosController / PosModuleController│
                    │  - SupplierController               │
                    │  - WarehouseController              │
                    │  - MasterDataController              │
                    │  - MasterPriceController             │
                    │  - DebtController                   │
                    │  - AccessControlController           │
                    │  - AuthController                   │
                    │  - ReturnController                 │
                    │  - CashierShiftController            │
                    │  - ServiceOrderController            │
                    │  - StockAdjustmentController         │
                    │  - WarehouseTransferController       │
                    │  - SupplierPayableController         │
                    │  - VoucherController                │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │       SERVICE LAYER                  │
                    │  (Business Logic)                    │
                    │  - InventoryService                 │
                    │    • FIFO stock locking             │
                    │    • Bundle handling                │
                    │    • Stock refresh                  │
                    │  - PriceCatalogService              │
                    │    • Price updates                  │
                    │    • Excel/CSV imports              │
                    │    • Selling price calculation      │
                    │  - TaxService                       │
                    │    • PPN (11% if PKP)               │
                    │    • PPh 22/23/21                   │
                    │    • DPP goods/services split       │
                    │  - UomConversionService             │
                    │    • Convert to base UOM            │
                    │    • Direct & reverse factor        │
                    │  - ReturnService                    │
                    │    • Purchase return (FIFO out)     │
                    │    • Sales return (batch restore)   │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │        MODEL LAYER                   │
                    │  (36 Eloquent Models)                │
                    │  - Product, Category, Supplier       │
                    │  - Purchase, PurchaseItem           │
                    │  - Sale, SaleItem                   │
                    │  - InventoryBatch, GoodReceive       │
                    │  - Customer, CustomerDebt            │
                    │  - User, Role, Menu, RolePermission  │
                    │  - MasterPrice, GlobalMaster        │
                    │  - Warehouse, WarehouseRack         │
                    │  - BundleItem, ProductUomConversion  │
                    │  - PurchaseReturn, PurchaseReturnItem│
                    │  - SalesReturn, SalesReturnItem     │
                    │  - CashierShift                     │
                    │  - ServiceOrder, StockAdjustment     │
                    │  - WarehouseTransfer, SupplierPayable│
                    │  - Voucher, VoucherUsage            │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │      DATABASE LAYER                 │
                    │  (PostgreSQL)                        │
                    │  - RBAC tables                      │
                    │  - Master data tables               │
                    │  - Inventory tables                 │
                    │  - Purchase/Sales tables            │
                    │  - Tax/debt tables                  │
                    └─────────────────────────────────────┘
```

## Module Structure

```
Bengkel Berkah POS System
├── 1. DASHBOARD MODULE
│   ├── Daily sales summary
│   ├── Open debt alerts
│   └── Low stock notifications
│
├── 2. INVENTORY MODULE (Warehouse)
│   ├── Category Management
│   ├── Product & Sparepart Management
│   ├── Bundle/Promo Packages
│   ├── Stock Ledger (Card)
│   └── Barcode/QR Code Generation
│
├── 3. PURCHASING MODULE
│   ├── Supplier Management
│   ├── Purchase Orders (PO)
│   ├── Good Receive (GR)
│   ├── FIFO Batch Creation
│   └── Purchase History
│
├── 4. POS MODULE (Sales & Cashier)
│   ├── Open Cashier (Cart)
│   ├── Product Lookup (Barcode/Search)
│   ├── Draft/Hold Transactions
│   ├── Payment Processing
│   └── Sales History
│
├── 5. CUSTOMER & DEBT MODULE
│   ├── Customer Database
│   ├── Debt Tracking
│   └── Partial Payments
│
├── 6. REPORTING MODULE
│   ├── Sales Reports
│   ├── Profit/Loss Reports
│   └── Tax Reports (PPN)
│
└── 7. SYSTEM & SETTINGS MODULE
    ├── Global Master Data
    ├── Voucher Management
    ├── RBAC (Users, Roles, Permissions)
    └── Store Settings
```

## Data Flow Diagram

### Purchase Flow (Stock In)
```
Supplier → Purchase Order → Good Receive → Inventory Batch (FIFO)
           ↓                  ↓              ↓
        Tax Calculation   Warehouse      Stock Update
        (PPN/PPh)         Selection      (total_stock)
```

### Sales Flow (Stock Out)
```
Customer → POS Cart → Stock Lock (FIFO) → Payment → Sale Completion
           ↓           ↓                    ↓         ↓
        Product    InventoryService    Debt      Final Sale
        Lookup     (lockForSale)       Tracking   Record
                   ↓
            Bundle Handling
            (if is_bundle)
```

### Price Management Flow
```
Manual Entry / Excel Import → PriceCatalogService → MasterPrice
                                   ↓
                            Active Price Update
                                   ↓
                            Selling Price Calc
                            (markup + cost)
```

## Database Schema Relationships

```
┌─────────────────┐       ┌─────────────────┐
│     USERS       │◄──────│      ROLES      │
└────────┬────────┘       └─────────────────┘
         │
         │
┌────────▼────────┐       ┌─────────────────┐
│ ROLE_PERMISSIONS│──────►│      MENUS      │
└─────────────────┘       └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│   CATEGORIES    │◄──────│    PRODUCTS     │
└─────────────────┘       └────────┬────────┘
                                   │
                    ┌──────────────┼──────────────┐
                    │              │              │
            ┌───────▼──────┐ ┌────▼────┐ ┌──────▼──────┐
            │PRODUCT_UOM_  │ │BUNDLE_  │ │INVENTORY_   │
            │CONVERSIONS   │ │ITEMS    │ │BATCHES      │
            └──────────────┘ └─────────┘ └──────┬──────┘
                                              │
                                              │ FIFO
                                    ┌─────────▼─────────┐
                                    │  PURCHASE_ITEMS   │
                                    └─────────┬─────────┘
                                              │
                                    ┌─────────▼─────────┐
                                    │    PURCHASES      │
                                    └─────────┬─────────┘
                                              │
                                    ┌─────────▼─────────┐
                                    │   SUPPLIERS       │
                                    └───────────────────┘

┌─────────────────┐       ┌─────────────────┐
│    CUSTOMERS    │◄──────│      SALES       │
└────────┬────────┘       └────────┬────────┘
         │                          │
┌────────▼────────┐       ┌────────▼────────┐
│ CUSTOMER_DEBTS  │       │    SALE_ITEMS   │
└────────┬────────┘       └────────┬────────┘
         │                          │
┌────────▼────────┐       ┌────────▼────────┐
│  DEBT_PAYMENTS  │       │INVENTORY_BATCHES│
└─────────────────┘       │(stock deduction)│
                          └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│   WAREHOUSES    │◄──────│INVENTORY_BATCHES│
└────────┬────────┘       └────────┬────────┘
         │                          │
┌────────▼────────┐       ┌────────▼────────┐
│ WAREHOUSE_RACKS │       │GOOD_RECEIVE_ITEMS│
└─────────────────┘       └────────┬────────┘
                                   │
                         ┌─────────▼─────────┐
                         │   GOOD_RECEIVES   │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │    PURCHASES      │
                         └───────────────────┘
```

## Key Business Logic Flows

### 1. FIFO Inventory Management
```
InventoryService::lockForSale()
├── Check if product is bundle
│   ├── YES: lockBundle()
│   │   └── Loop through bundle items
│   │       └── lockPhysicalProduct() for each component
│   └── NO: lockPhysicalProduct()
│       └── Get batches ordered by created_at (FIFO)
│           └── Deduct from oldest batch first
│               └── If insufficient, throw exception
└── Refresh product total_stock
```

### 2. Indonesian Tax Calculation (Purchase)
```
Purchase Order Tax Logic:
├── Separate DPP (Goods vs Services)
├── Calculate PPN (11% if supplier is PKP)
├── Calculate PPh:
│   ├── Goods (Barang):
│   │   └── PPh 22 (1.5%) if buyer is Government/BUMN
│   │   └── Otherwise 0%
│   └── Services (Jasa):
│       ├── Corporate (PT/CV): PPh 23 (2%)
│       └── Individual: PPh 21 (TER or progressive)
└── Grand Total = DPP + PPN - PPh
```

### 3. Bundle Product Handling
```
Bundle Sale Flow:
├── Product.is_bundle = true
├── No physical stock for bundle
├── Bundle Items define components
├── On sale:
│   └── Loop through bundle_items
│       └── Deduct component stock (FIFO)
└── Profit calculated from component costs
```

### 4. Price Catalog Management
```
Price Update Flow:
├── Manual or Excel Import
├── PriceCatalogService::updatePrice()
│   ├── Deactivate old active price
│   ├── Create new active price
│   └── Set effective dates
├── Selling Price Calculation:
│   └── Cost + Markup (fixed or percentage)
└── Priority: Supplier Price → Master Price → Last Batch Price
```

## Security & Access Control

```
RBAC System:
├── Users (with roles)
├── Roles (SuperAdmin, Cashier, Mechanic)
├── Menus (hierarchical structure)
└── Role Permissions (CRUD per menu)

Access Flow:
User Login → Role Assignment → Menu Permission Check → Route Access
```

## File Structure

```
BengkelBerkah/
├── app/
│   ├── Http/
│   │   └── Controllers/ (13 controllers)
│   ├── Models/ (28 Eloquent models)
│   ├── Services/ (Business logic)
│   └── Providers/ (Service providers)
├── database/
│   ├── migrations/ (20+ schema files)
│   ├── seeders/ (Initial data)
│   └── factories/ (Test data)
├── resources/
│   ├── views/ (Blade templates)
│   │   ├── layouts/ (app.blade.php)
│   │   ├── master/ (33 module views)
│   │   ├── pos/ (POS module)
│   │   ├── purchases/ (Purchase module)
│   │   └── debts/ (Debt module)
│   ├── css/ (TailwindCSS)
│   └── js/ (JavaScript)
├── routes/
│   ├── web.php (HTTP routes)
│   └── console.php (CLI routes)
├── config/ (Laravel configuration)
└── public/ (Public web root)
```

## Architecture Gaps & Future Modules

The following items reflect the current implementation status. Items marked **DONE** are implemented; items marked **PENDING** remain.

### 1. Business Modules

- **Service / Work Order Module** — **DONE** — multi-day service handling, mechanic assignment, status tracking.
- **Purchase & Sales Returns** — **DONE** — `ReturnController` + `ReturnService` handle both purchase returns (FIFO stock out) and sales returns (batch restore).
- **Stock Adjustment / Opname** — **DONE** — manual stock correction with batch-level adjustments.
- **Warehouse Transfer Module** — **DONE** — move stock between warehouses/racks.
- **Supplier Payable (Account Payable)** — **DONE** — tracking payments and remaining debt to suppliers.
- **Cashier Shift / Cash Drawer Management** — **DONE** — open/close shift, opening cash, counted closing cash, difference reconciliation.

### 2. Data Flows

- **UOM Auto-Conversion** — **DONE** — `UomConversionService` converts purchased/sold UOM to base UOM (direct + reverse factor). PO auto-calculates `qty_in_base_uom`; POS accepts optional `uom_code[]`.
- **Voucher Application in POS** — **DONE** — voucher code reduces sale grand total via `applyVoucher`.
- **Debt Payment Flow** — **DONE** — `customer_debts` → `debt_payments` partial payments.
- **Cancel / Unhold Transaction** — **DONE** — `PosModuleController::destroyDraft()` releases locked stock back to `inventory_batches`.
- **Barcode/QR Generation & Print Labels** — **DONE** — product barcode storage + printable labels.
- **Bulk Price Import** — **DONE** — CSV/Excel import via `price_import_batches` and `price_import_lines`.

### 3. Service Layer

- **TaxService** — **DONE** — PPN, PPh 22/23/21, DPP split, grand total.
- **UomConversionService** — **DONE** — convertToBaseUom, getAvailableUoms.
- **ReturnService** — **DONE** — processPurchaseReturn, processSalesReturn.
- **PurchaseService / PosService / StockMovementService** — **PENDING** — currently logic is inline in controllers.

### 4. Technical & Infrastructure Layers

- **RBAC Middleware** — **DONE** — `EnsureMenuPermission` middleware enforces per-menu CRUD permissions.
- **Testing** — **PARTIAL** — 18 tests pass (TaxService, UomConversionService, RBAC). FIFO/bundle/POS payment tests pending.
- **Deployment Architecture** — **PENDING** — web server, queue worker, scheduler documentation.
- **Authentication** — **DONE** — login UI implemented, AuthController with login/logout.

## API Endpoints Summary

### Authentication
- `GET /login` - Login form
- `POST /login` - Login submit
- `POST /logout` - Logout

### Dashboard
- `GET /` - Dashboard

### Master Data
- `GET /master-data` - Master data overview
- `POST /master-data/products` - Create product
- `POST /master-data/customers` - Create customer
- `POST /master-data/suppliers` - Create supplier

### Inventory
- `GET /master/inventory` - Product list
- `POST /master/inventory` - Create product
- `PATCH /master/inventory/{id}/activate` - Activate product
- `GET /master/inventory/lookup/*` - Various lookups

### Purchasing
- `GET /purchases` - Purchase list
- `POST /purchases` - Create purchase
- `GET /purchases/{id}/receive` - Good receive form
- `POST /purchases/{id}/receive` - Process good receive

### POS
- `GET /modules/pos/open-cashier` - Open cashier
- `GET /modules/pos/lookup-products` - Product lookup
- `POST /modules/pos/save-draft` - Save draft transaction
- `POST /modules/pos/payment/{sale}` - Process payment

### Debt Management
- `GET /debts` - Debt list
- `POST /debts/{id}/payments` - Record payment

### System Administration
- `GET /master/users` - User management
- `GET /master/menus` - Menu management
- `GET /master/roles` - Role management
- `GET /master/prices` - Price catalog

### Returns
- `GET /returns/purchases` - Purchase return list
- `GET /returns/purchases/create` - Create form
- `POST /returns/purchases` - Store purchase return
- `POST /returns/purchases/{id}/approve` - Approve (deduct stock)
- `GET /returns/sales` - Sales return list
- `POST /returns/sales` - Store sales return
- `POST /returns/sales/{id}/approve` - Approve (restore stock)

### Cashier Shifts
- `GET /cashier-shifts` - Shift history
- `GET /cashier-shifts/status` - Current shift status
- `POST /cashier-shifts/open` - Open new shift
- `POST /cashier-shifts/close` - Close shift with counted cash
- `GET /cashier-shifts/{id}` - Shift detail / reconciliation
