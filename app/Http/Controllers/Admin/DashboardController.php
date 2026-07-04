<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Item;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\SalesOrder;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\WarehouseTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard.index', [
            'stats' => [
                'Total Items' => Item::count(),
                'Total Warehouses' => Warehouse::count(),
                'Stock Low' => StockBalance::where('quantity_on_hand', '<=', 0)->count(),
                'Purchase This Month' => PurchaseOrder::whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->count(),
                'Sales This Month' => SalesOrder::whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->count(),
                'Pending Receivings' => Receiving::where('status', 'draft')->count(),
            ],
            'quickActions' => [
                ['label' => 'New Purchase Order', 'route' => 'purchase-orders.create', 'module' => 'purchase'],
                ['label' => 'Receive Supplier', 'route' => 'receivings.create', 'module' => 'purchase'],
                ['label' => 'New Sales Order', 'route' => 'sales-orders.create', 'module' => 'sales'],
                ['label' => 'Warehouse Transfer', 'route' => 'warehouse-transfers.create', 'module' => 'inventory'],
                ['label' => 'Stock Adjustment', 'route' => 'stock-adjustments.create', 'module' => 'inventory'],
                ['label' => 'Repacking Order', 'route' => 'productions.create', 'module' => 'production'],
            ],
            'balances' => StockBalance::with(['item', 'warehouse'])->latest('updated_at')->limit(8)->get(),
            'recentDocuments' => $this->recentDocuments(),
            'lowStocks' => StockBalance::with(['item', 'warehouse'])
                ->where('quantity_on_hand', '<=', 0)
                ->latest('updated_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function recentDocuments(): Collection
    {
        return collect()
            ->merge(PurchaseOrder::with('supplier')->latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'PO',
                'number' => $record->number,
                'partner' => $record->supplier?->name,
                'date' => $record->order_date,
                'status' => $record->status,
                'total' => $record->grand_total,
                'route' => route('purchase-orders.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(Receiving::with('supplier')->latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'RCV',
                'number' => $record->number,
                'partner' => $record->supplier?->name,
                'date' => $record->received_date,
                'status' => $record->status,
                'total' => null,
                'route' => route('receivings.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(SalesOrder::with('customer')->latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'SO',
                'number' => $record->number,
                'partner' => $record->customer?->name,
                'date' => $record->order_date,
                'status' => $record->status,
                'total' => $record->grand_total,
                'route' => route('sales-orders.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(DeliveryOrder::with('customer')->latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'DO',
                'number' => $record->number,
                'partner' => $record->customer?->name,
                'date' => $record->delivery_date,
                'status' => $record->status,
                'total' => null,
                'route' => route('delivery-orders.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(WarehouseTransfer::latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'TRF',
                'number' => $record->number,
                'partner' => null,
                'date' => $record->transfer_date,
                'status' => $record->status,
                'total' => null,
                'route' => route('warehouse-transfers.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(StockAdjustment::latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'ADJ',
                'number' => $record->number,
                'partner' => null,
                'date' => $record->adjustment_date,
                'status' => $record->status,
                'total' => null,
                'route' => route('stock-adjustments.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->merge(Production::latest('created_at')->limit(4)->get()->map(fn ($record) => [
                'type' => 'PRD',
                'number' => $record->number,
                'partner' => null,
                'date' => $record->production_date,
                'status' => $record->status,
                'total' => null,
                'route' => route('productions.show', $record),
                'created_at' => $record->created_at,
            ]))
            ->sortByDesc('created_at')
            ->take(8)
            ->values();
    }
}
