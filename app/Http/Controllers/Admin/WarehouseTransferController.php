<?php

namespace App\Http\Controllers\Admin;

use App\Models\Warehouse;
use App\Models\WarehouseTransfer;

class WarehouseTransferController extends ResourceController
{
    protected string $model = WarehouseTransfer::class;
    protected string $route = 'warehouse-transfers';
    protected string $title = 'Warehouse Transfer';
    protected ?string $documentType = 'warehouse_transfer';
    protected array $with = ['fromWarehouse', 'toWarehouse'];
    protected array $columns = ['number', 'fromWarehouse.name', 'toWarehouse.name', 'transfer_date', 'status'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'from_warehouse_id' => ['required', 'integer'], 'to_warehouse_id' => ['required', 'integer'], 'transfer_date' => ['required', 'date'], 'status' => ['required', 'string'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $warehouses = Warehouse::orderBy('name')->pluck('name', 'id')->toArray();
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'from_warehouse_id' => ['label' => 'From Warehouse', 'type' => 'select', 'options' => $warehouses], 'to_warehouse_id' => ['label' => 'To Warehouse', 'type' => 'select', 'options' => $warehouses], 'transfer_date' => ['label' => 'Transfer Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
