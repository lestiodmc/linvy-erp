<?php

use App\Http\Controllers\Admin\AccountingAccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryOrderController;
use App\Http\Controllers\Admin\ItemCategoryController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReceivingController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockBalanceController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UnitOfMeasureController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\WarehouseTransferController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('accounting-accounts', AccountingAccountController::class)->parameters(['accounting-accounts' => 'record']);
    Route::resource('units-of-measure', UnitOfMeasureController::class)->parameters(['units-of-measure' => 'record']);
    Route::resource('item-categories', ItemCategoryController::class)->parameters(['item-categories' => 'record']);
    Route::resource('items', ItemController::class)->parameters(['items' => 'record']);
    Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'record']);
    Route::resource('customers', CustomerController::class)->parameters(['customers' => 'record']);
    Route::resource('warehouses', WarehouseController::class)->parameters(['warehouses' => 'record']);

    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'show'])->parameters(['stock-movements' => 'record']);
    Route::resource('stock-balances', StockBalanceController::class)->only(['index', 'show'])->parameters(['stock-balances' => 'record']);
    Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase-orders' => 'record']);
    Route::resource('receivings', ReceivingController::class)->parameters(['receivings' => 'record']);
    Route::resource('warehouse-transfers', WarehouseTransferController::class)->parameters(['warehouse-transfers' => 'record']);
    Route::resource('stock-adjustments', StockAdjustmentController::class)->parameters(['stock-adjustments' => 'record']);
    Route::resource('productions', ProductionController::class)->parameters(['productions' => 'record']);
    Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'record']);
    Route::resource('delivery-orders', DeliveryOrderController::class)->parameters(['delivery-orders' => 'record']);
});

require __DIR__.'/auth.php';
