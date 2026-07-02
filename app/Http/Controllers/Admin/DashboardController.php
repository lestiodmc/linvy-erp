<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'stats' => [
                'Items' => Item::count(),
                'Warehouses' => Warehouse::count(),
                'Suppliers' => Supplier::count(),
                'Customers' => Customer::count(),
                'Purchase Orders' => PurchaseOrder::count(),
                'Receivings' => Receiving::count(),
                'Productions' => Production::count(),
                'Delivery Orders' => DeliveryOrder::count(),
            ],
            'balances' => StockBalance::with(['item', 'warehouse'])->latest('updated_at')->limit(8)->get(),
        ]);
    }
}
