<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PurchaseLookupController extends Controller
{
    public function items(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $items = Item::query()
            ->with(['category:id,name', 'unitOfMeasure:id,code,name', 'purchaseUnit:id,code,name', 'baseUnit:id,code,name'])
            ->when($this->hasColumn('items', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->where(function ($query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$search.'%'));

                if ($this->hasColumn('items', 'code')) {
                    $query->orWhere('code', 'like', '%'.$search.'%');
                }
            })
            ->orderBy('sku')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'description', 'unit_of_measure_id', 'base_unit_id', 'purchase_unit_id', 'default_warehouse_type_id']);

        return response()->json($items->map(function (Item $item): array {
            $unit = $item->purchaseUnit ?: ($item->unitOfMeasure ?: $item->baseUnit);

            return [
                'id' => $item->id,
                'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
                'description' => $item->description,
                'unit_id' => $unit?->id,
                'unit_text' => $unit?->code ?: $unit?->name,
                'default_warehouse_type_id' => $item->default_warehouse_type_id,
            ];
        })->values());
    }

    public function suppliers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $suppliers = Supplier::query()
            ->when($this->hasColumn('suppliers', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');

                foreach (['code', 'phone', 'email'] as $column) {
                    if ($this->hasColumn('suppliers', $column)) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'code', 'name']);

        return response()->json($suppliers->map(fn (Supplier $supplier): array => [
            'id' => $supplier->id,
            'text' => trim(($supplier->code ? $supplier->code.' - ' : '').$supplier->name),
        ])->values());
    }

    public function purchaseOrders(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $orders = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->whereIn('status', ['approved', 'partially_received'])
            ->when(! Auth::user()?->isSuperAdmin(), function ($query): void {
                $branchIds = Auth::user()?->branches()->pluck('branches.id') ?? [];
                $query->where(function ($branchQuery) use ($branchIds): void {
                    $branchQuery->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
                });
            })
            ->where(function ($query) use ($search): void {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$search.'%'));
            })
            ->latest('id')
            ->limit(20)
            ->get(['id', 'number', 'supplier_id', 'branch_id']);

        return response()->json($orders->map(fn (PurchaseOrder $order): array => [
            'id' => $order->id,
            'text' => trim($order->number.' - '.$order->supplier?->name),
        ])->values());
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}
