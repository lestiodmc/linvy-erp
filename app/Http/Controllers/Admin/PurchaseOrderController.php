<?php

namespace App\Http\Controllers\Admin;

use App\Models\PurchaseOrder;
use App\Models\Supplier;

class PurchaseOrderController extends ResourceController
{
    protected string $model = PurchaseOrder::class;
    protected string $route = 'purchase-orders';
    protected string $title = 'Purchase Order';
    protected ?string $documentType = 'purchase_order';
    protected array $with = ['supplier'];
    protected array $columns = ['number', 'supplier.name', 'order_date', 'expected_date', 'status', 'grand_total'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'supplier_id' => ['required', 'integer'], 'order_date' => ['required', 'date'], 'expected_date' => ['nullable', 'date'], 'status' => ['required', 'string'], 'subtotal' => ['required', 'numeric'], 'tax_total' => ['required', 'numeric'], 'grand_total' => ['required', 'numeric'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'supplier_id' => ['label' => 'Supplier', 'type' => 'select', 'options' => Supplier::orderBy('name')->pluck('name', 'id')->toArray()], 'order_date' => ['label' => 'Order Date', 'type' => 'date'], 'expected_date' => ['label' => 'Expected Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'approved' => 'Approved', 'partially_received' => 'Partially Received', 'received' => 'Received', 'cancelled' => 'Cancelled']], 'subtotal' => ['label' => 'Subtotal', 'type' => 'number', 'step' => '0.0001'], 'tax_total' => ['label' => 'Tax Total', 'type' => 'number', 'step' => '0.0001'], 'grand_total' => ['label' => 'Grand Total', 'type' => 'number', 'step' => '0.0001'], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
