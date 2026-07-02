<?php

use App\Http\Controllers\Admin\AccountingAccountController;
use App\Http\Controllers\Admin\AccountMappingController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryOrderController;
use App\Http\Controllers\Admin\DocumentSequenceController;
use App\Http\Controllers\Admin\ItemCategoryController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ModuleSettingController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReceivingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockBalanceController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UnitOfMeasureController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\WarehouseTransferController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'module:dashboard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('module:master-data')->group(function () {
        Route::resource('units-of-measure', UnitOfMeasureController::class)->parameters(['units-of-measure' => 'record']);
        Route::resource('item-categories', ItemCategoryController::class)->parameters(['item-categories' => 'record']);
        Route::resource('items', ItemController::class)->parameters(['items' => 'record']);
        Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'record']);
        Route::resource('customers', CustomerController::class)->parameters(['customers' => 'record']);
        Route::resource('warehouses', WarehouseController::class)->parameters(['warehouses' => 'record']);
    });

    Route::middleware('module:purchase')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase-orders' => 'record']);
        Route::resource('receivings', ReceivingController::class)->parameters(['receivings' => 'record']);
    });

    Route::middleware('module:inventory')->group(function () {
        Route::resource('stock-movements', StockMovementController::class)->only(['index', 'show'])->parameters(['stock-movements' => 'record']);
        Route::resource('stock-balances', StockBalanceController::class)->only(['index', 'show'])->parameters(['stock-balances' => 'record']);
        Route::resource('warehouse-transfers', WarehouseTransferController::class)->parameters(['warehouse-transfers' => 'record']);
        Route::resource('stock-adjustments', StockAdjustmentController::class)->parameters(['stock-adjustments' => 'record']);
    });

    Route::middleware('module:production')->group(function () {
        Route::resource('productions', ProductionController::class)->parameters(['productions' => 'record']);
    });

    Route::middleware('module:sales')->group(function () {
        Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'record']);
        Route::resource('delivery-orders', DeliveryOrderController::class)->parameters(['delivery-orders' => 'record']);
    });

    Route::middleware('module:accounting')->group(function () {
        Route::get('account-mapping', AccountMappingController::class)->name('account-mapping.index');
        Route::resource('accounting-accounts', AccountingAccountController::class)->parameters(['accounting-accounts' => 'record']);
    });

    Route::middleware('module:settings')->group(function () {
        Route::get('module-settings', [ModuleSettingController::class, 'index'])->name('module-settings.index');
        Route::put('module-settings', [ModuleSettingController::class, 'update'])->name('module-settings.update');
        Route::resource('users', UserController::class)->parameters(['users' => 'record']);
        Route::resource('roles', RoleController::class)->parameters(['roles' => 'record']);
        Route::resource('document-sequences', DocumentSequenceController::class)->parameters(['document-sequences' => 'record']);
    });
});

require __DIR__.'/auth.php';
