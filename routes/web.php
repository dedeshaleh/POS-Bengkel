<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\Modules\Inventory\CategoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPayableController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WarehouseTransferController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'menu.permission'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/api/alerts', [DashboardController::class, 'alerts'])->name('api.alerts');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Test-only route for RBAC middleware tests (returns 200 OK)
    if (app()->environment('testing')) {
        Route::get('/test-rbac', fn () => response('OK'))->name('test.rbac');
        Route::post('/test-rbac', fn () => response('OK'));
    }

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/master-data/products', [MasterDataController::class, 'storeProduct'])->name('products.store');
    Route::post('/master-data/customers', [MasterDataController::class, 'storeCustomer'])->name('customers.store');
    Route::post('/master-data/suppliers', [MasterDataController::class, 'storeSupplier'])->name('suppliers.store');

    Route::resource('/master/suppliers', SupplierController::class)->names('master.suppliers')->except(['destroy']);
    Route::get('/master/suppliers/{supplier}/lookup/products', [SupplierController::class, 'lookupProducts'])->name('master.suppliers.lookup.products');
    Route::post('/master/suppliers/{supplier}/products', [SupplierController::class, 'attachProduct'])->name('master.suppliers.products.attach');
    Route::delete('/master/suppliers/{supplier}/products/{product}', [SupplierController::class, 'detachProduct'])->name('master.suppliers.products.detach');
    Route::patch('/master/suppliers/{supplier}/activate', [SupplierController::class, 'activate'])->name('master.suppliers.activate');
    Route::patch('/master/suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])->name('master.suppliers.deactivate');

    Route::get('/master/inventory/lookup/categories', [InventoryController::class, 'lookupCategories'])->name('master.inventory.lookup.categories');
    Route::get('/master/inventory/lookup/masters/{categoryCode}', [InventoryController::class, 'lookupGlobalMasters'])->name('master.inventory.lookup.masters');
    Route::get('/master/inventory/lookup/components', [InventoryController::class, 'lookupComponents'])->name('master.inventory.lookup.components');
    Route::get('/master/inventory/{product}/print-code', [InventoryController::class, 'printCode'])->name('master.inventory.print-code');
    Route::resource('/master/inventory', InventoryController::class)->names('master.inventory')->parameters(['inventory' => 'product'])->except(['destroy']);
    Route::patch('/master/inventory/{product}/activate', [InventoryController::class, 'activate'])->name('master.inventory.activate');
    Route::patch('/master/inventory/{product}/deactivate', [InventoryController::class, 'deactivate'])->name('master.inventory.deactivate');


    Route::resource('/master/warehouses', WarehouseController::class)->names('master.warehouses')->except(['show', 'destroy']);
    Route::patch('/master/warehouses/{warehouse}/activate', [WarehouseController::class, 'activate'])->name('master.warehouses.activate');
    Route::patch('/master/warehouses/{warehouse}/deactivate', [WarehouseController::class, 'deactivate'])->name('master.warehouses.deactivate');
    Route::get('/master/warehouses/{warehouse}/racks', [WarehouseController::class, 'racks'])->name('master.warehouses.racks.index');
    Route::post('/master/warehouses/{warehouse}/racks', [WarehouseController::class, 'storeRack'])->name('master.warehouses.racks.store');
    Route::put('/master/warehouses/racks/{rack}', [WarehouseController::class, 'updateRack'])->name('master.warehouses.racks.update');
    Route::delete('/master/warehouses/racks/{rack}', [WarehouseController::class, 'deleteRack'])->name('master.warehouses.racks.delete');
    Route::patch('/master/warehouses/racks/{rack}/activate', [WarehouseController::class, 'activateRack'])->name('master.warehouses.racks.activate');
    Route::patch('/master/warehouses/racks/{rack}/deactivate', [WarehouseController::class, 'deactivateRack'])->name('master.warehouses.racks.deactivate');

    Route::get('/master/prices', [MasterPriceController::class, 'index'])->name('master.prices.index');
    Route::post('/master/prices', [MasterPriceController::class, 'store'])->name('master.prices.store');
    Route::get('/master/prices/import', [MasterPriceController::class, 'importForm'])->name('master.prices.import');
    Route::get('/master/prices/import/template', [MasterPriceController::class, 'downloadTemplate'])->name('master.prices.import.template');
    Route::post('/master/prices/import', [MasterPriceController::class, 'import'])->name('master.prices.import.store');
    Route::get('/master/prices/{product}', [MasterPriceController::class, 'history'])->name('master.prices.history');

    Route::get('/master/users', [AccessControlController::class, 'users'])->name('master.users');
    Route::get('/master', fn () => redirect()->route('master.users'))->name('master.index');
    Route::get('/master/users/create', [AccessControlController::class, 'createUser'])->name('master.users.create');
    Route::get('/master/users/{user}', [AccessControlController::class, 'showUser'])->name('master.users.show');
    Route::get('/master/users/{user}/edit', [AccessControlController::class, 'editUser'])->name('master.users.edit');
    Route::get('/master/users/{user}/delete', [AccessControlController::class, 'confirmDeactivateUser'])->name('master.users.delete');
    Route::post('/master/users', [AccessControlController::class, 'storeUser'])->name('master.users.store');
    Route::put('/master/users/{user}', [AccessControlController::class, 'updateUser'])->name('master.users.update');
    Route::patch('/master/users/{user}/deactivate', [AccessControlController::class, 'deactivateUser'])->name('master.users.deactivate');
    Route::patch('/master/users/{user}/activate', [AccessControlController::class, 'activateUser'])->name('master.users.activate');
    Route::get('/master/menus', [AccessControlController::class, 'menus'])->name('master.menus');
    Route::get('/master/menus/create', [AccessControlController::class, 'createMenu'])->name('master.menus.create');
    Route::get('/master/menus/{menu}', [AccessControlController::class, 'showMenu'])->name('master.menus.show');
    Route::get('/master/menus/{menu}/edit', [AccessControlController::class, 'editMenu'])->name('master.menus.edit');
    Route::get('/master/menus/{menu}/delete', [AccessControlController::class, 'confirmDeleteMenu'])->name('master.menus.delete');
    Route::post('/master/menus', [AccessControlController::class, 'storeMenu'])->name('master.menus.store');
    Route::put('/master/menus/{menu}', [AccessControlController::class, 'updateMenu'])->name('master.menus.update');
    Route::delete('/master/menus/{menu}', [AccessControlController::class, 'destroyMenu'])->name('master.menus.destroy');
    Route::get('/master/menus/access/create', [AccessControlController::class, 'createRoleAccess'])->name('master.menus.access.create');
    Route::get('/master/menus/access/{access}/edit', [AccessControlController::class, 'editRoleAccess'])->name('master.menus.access.edit');
    Route::get('/master/menus/access/{access}/delete', [AccessControlController::class, 'confirmDeleteRoleAccess'])->name('master.menus.access.delete');
    Route::post('/master/menus/access', [AccessControlController::class, 'saveRoleMenuAccess'])->name('master.menus.access');
    Route::put('/master/menus/access/{access}', [AccessControlController::class, 'updateRoleAccess'])->name('master.menus.access.update');
    Route::delete('/master/menus/access/{access}', [AccessControlController::class, 'destroyRoleAccess'])->name('master.menus.access.destroy');
    Route::get('/master/roles', [AccessControlController::class, 'roles'])->name('master.roles');
    Route::post('/master/roles', [AccessControlController::class, 'storeRole'])->name('master.roles.store');

    Route::get('/purchases/lookup/products', [PurchaseController::class, 'lookupProducts'])->name('purchases.lookup.products');
    Route::get('/purchases/lookup/suppliers', [PurchaseController::class, 'lookupSuppliers'])->name('purchases.lookup.suppliers');
    Route::get('/purchases/products/{product}/last-price', [PurchaseController::class, 'lastPrice'])->name('purchases.products.last-price');
    Route::get('/purchases/products/{product}/uoms', [PurchaseController::class, 'lookupUoms'])->name('purchases.products.uoms');
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
    Route::patch('/purchases/{purchase}/activate', [PurchaseController::class, 'activate'])->name('purchases.activate');
    Route::patch('/purchases/{purchase}/close', [PurchaseController::class, 'close'])->name('purchases.close');
    Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'printPo'])->name('purchases.print');
    Route::get('/purchases/{purchase}/good-receives/{goodReceive}/print', [PurchaseController::class, 'printGoodReceive'])->name('purchases.good-receives.print');
    Route::get('/purchases/{purchase}/receive', [PurchaseController::class, 'receiveForm'])->name('purchases.receive.form');
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');

    // Legacy POS route now redirects to the new open-cashier POS module
    Route::get('/pos', function () {
        return redirect()->route('modules.pos.open-cashier');
    })->name('pos.create');

    // New POS Module (Open Cashier with unlimited items, draft, barcode, real-time stock)
    Route::prefix('modules/pos')->name('modules.pos.')->group(function () {
        Route::get('/open-cashier', [\App\Http\Controllers\PosModuleController::class, 'openCashier'])->name('open-cashier');
        Route::get('/lookup-products', [\App\Http\Controllers\PosModuleController::class, 'lookupProducts'])->name('lookup-products');
        Route::get('/lookup-uoms/{product}', [\App\Http\Controllers\PosModuleController::class, 'lookupUoms'])->name('lookup-uoms');
        Route::get('/lookup-customers', [\App\Http\Controllers\PosModuleController::class, 'lookupCustomers'])->name('lookup-customers');
        Route::post('/save-draft', [\App\Http\Controllers\PosModuleController::class, 'saveDraft'])->name('save-draft');
        Route::get('/payment/{sale}', [\App\Http\Controllers\PosModuleController::class, 'showPayment'])->name('payment');
        Route::post('/payment/{sale}', [\App\Http\Controllers\PosModuleController::class, 'processPayment'])->name('process-payment');
        Route::delete('/draft/{sale}', [\App\Http\Controllers\PosModuleController::class, 'destroyDraft'])->name('destroy-draft');
        Route::post('/apply-voucher', [\App\Http\Controllers\PosModuleController::class, 'applyVoucher'])->name('apply-voucher');
        Route::post('/quick-add-customer', [\App\Http\Controllers\PosModuleController::class, 'quickAddCustomer'])->name('quick-add-customer');
        Route::get('/receipt/{sale}', [\App\Http\Controllers\PosModuleController::class, 'showReceipt'])->name('receipt');
        Route::post('/check-stock', [\App\Http\Controllers\PosModuleController::class, 'checkStock'])->name('check-stock');
        Route::get('/realtime-stock', [\App\Http\Controllers\PosModuleController::class, 'realtimeStock'])->name('realtime-stock');
    });

    // Voucher CRUD
    Route::resource('/master/vouchers', VoucherController::class)->names('vouchers')->except(['show']);

    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('/debts/{debt}/payments', [DebtController::class, 'pay'])->name('debts.pay');

    Route::prefix('modules/reporting')->name('modules.reporting.')->group(function () {
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
        Route::get('/tax', [ReportController::class, 'tax'])->name('tax');
    });

    Route::resource('service-orders', ServiceOrderController::class)->except(['destroy']);
    Route::post('service-orders/{serviceOrder}/complete-and-pay', [ServiceOrderController::class, 'completeAndPay'])->name('service-orders.complete-and-pay');
    Route::post('service-orders/{serviceOrder}/send-to-pos', [ServiceOrderController::class, 'sendToPos'])->name('service-orders.send-to-pos');

    Route::get('/stock-adjustments/{stockAdjustment}/finalize', [StockAdjustmentController::class, 'showFinalize'])
        ->name('stock-adjustments.finalize.show');
    Route::post('/stock-adjustments/{stockAdjustment}/finalize', [StockAdjustmentController::class, 'finalize'])
        ->name('stock-adjustments.finalize');
    Route::resource('stock-adjustments', StockAdjustmentController::class)->except(['destroy']);

    Route::get('/warehouse-transfers/{warehouseTransfer}/finalize', [WarehouseTransferController::class, 'showFinalize'])
        ->name('warehouse-transfers.finalize.show');
    Route::post('/warehouse-transfers/{warehouseTransfer}/finalize', [WarehouseTransferController::class, 'finalize'])
        ->name('warehouse-transfers.finalize');
    Route::resource('warehouse-transfers', WarehouseTransferController::class)->except(['destroy']);

    Route::get('/supplier-payables', [SupplierPayableController::class, 'index'])->name('supplier-payables.index');
    Route::get('/supplier-payables/create', [SupplierPayableController::class, 'create'])->name('supplier-payables.create');
    Route::post('/supplier-payables', [SupplierPayableController::class, 'store'])->name('supplier-payables.store');
    Route::get('/supplier-payables/{payable}', [SupplierPayableController::class, 'show'])->name('supplier-payables.show');
    Route::get('/supplier-payables/{payable}/pay', [SupplierPayableController::class, 'pay'])->name('supplier-payables.pay');
    Route::post('/supplier-payables/{payable}/pay', [SupplierPayableController::class, 'pay'])->name('supplier-payables.pay.store');

    Route::prefix('modules/inventory')->name('modules.inventory.')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/activate', [CategoryController::class, 'activate'])->name('categories.activate');
        Route::patch('/categories/{category}/deactivate', [CategoryController::class, 'deactivate'])->name('categories.deactivate');
    });

    // Returns (Retur Pembelian & Penjualan)
    Route::prefix('returns/purchases')->name('returns.purchases.')->group(function () {
        Route::get('/', [ReturnController::class, 'purchaseIndex'])->name('index');
        Route::get('/create', [ReturnController::class, 'purchaseCreate'])->name('create');
        Route::post('/', [ReturnController::class, 'purchaseStore'])->name('store');
        Route::get('/{purchaseReturn}', [ReturnController::class, 'purchaseShow'])->name('show');
        Route::post('/{purchaseReturn}/approve', [ReturnController::class, 'purchaseApprove'])->name('approve');
    });

    Route::prefix('returns/sales')->name('returns.sales.')->group(function () {
        Route::get('/', [ReturnController::class, 'salesIndex'])->name('index');
        Route::get('/create', [ReturnController::class, 'salesCreate'])->name('create');
        Route::post('/', [ReturnController::class, 'salesStore'])->name('store');
        Route::get('/{salesReturn}', [ReturnController::class, 'salesShow'])->name('show');
        Route::post('/{salesReturn}/approve', [ReturnController::class, 'salesApprove'])->name('approve');
    });

    // Cashier Shift / Cash Drawer
    Route::prefix('cashier-shifts')->name('cashier-shifts.')->group(function () {
        Route::get('/', [CashierShiftController::class, 'index'])->name('index');
        Route::get('/status', [CashierShiftController::class, 'status'])->name('status');
        Route::post('/open', [CashierShiftController::class, 'open'])->name('open');
        Route::post('/close', [CashierShiftController::class, 'close'])->name('close');
        Route::get('/{cashierShift}', [CashierShiftController::class, 'show'])->name('show');
    });

    Route::get('/modules/{module}/{feature?}', function (string $module, ?string $feature = null) {
        $moduleLabel = str($module)->replace('-', ' ')->title();
        $featureLabel = $feature ? str($feature)->replace('-', ' ')->title() : 'Overview';
        return view('module-progress', [
            'moduleName' => $moduleLabel,
            'featureName' => $featureLabel,
        ]);
    })->name('modules.progress');
});
