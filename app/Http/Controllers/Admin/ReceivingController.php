<?php

namespace App\Http\Controllers\Admin;

use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\Supplier;
use App\Models\Warehouse;

class ReceivingController extends ResourceController
{
    protected string $model = Receiving::class;
    protected string $route = 'receivings';
    protected string $title = 'Receiving';
    protected array $with = ['supplier', 'warehouse'];
    protected array $columns = ['number', 'supplier.name', 'warehouse.name', 'received_date', 'status', 'supplier_delivery_number'];
    protected array $rules = ['number' => ['required', 'string', 'max:255'], 'purchase_order_id' => ['nullable', 'integer'], 'supplier_id' => ['required', 'integer'], 'warehouse_id' => ['required', 'integer'], 'received_date' => ['required', 'date'], 'status' => ['required', 'string'], 'supplier_delivery_number' => ['nullable', 'string'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'purchase_order_id' => ['label' => 'Purchase Order', 'type' => 'select', 'options' => PurchaseOrder::orderBy('number')->pluck('number', 'id')->toArray(), 'nullable' => true], 'supplier_id' => ['label' => 'Supplier', 'type' => 'select', 'options' => Supplier::orderBy('name')->pluck('name', 'id')->toArray()], 'warehouse_id' => ['label' => 'Warehouse', 'type' => 'select', 'options' => Warehouse::orderBy('name')->pluck('name', 'id')->toArray()], 'received_date' => ['label' => 'Received Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']], 'supplier_delivery_number' => ['label' => 'Supplier Delivery Number', 'type' => 'text'], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
