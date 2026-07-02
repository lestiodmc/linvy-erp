<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\SalesOrder;

class SalesOrderController extends ResourceController
{
    protected string $model = SalesOrder::class;
    protected string $route = 'sales-orders';
    protected string $title = 'Sales Order';
    protected ?string $documentType = 'sales_order';
    protected array $with = ['customer'];
    protected array $columns = ['number', 'customer.name', 'order_date', 'requested_delivery_date', 'status', 'grand_total'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'customer_id' => ['required', 'integer'], 'order_date' => ['required', 'date'], 'requested_delivery_date' => ['nullable', 'date'], 'status' => ['required', 'string'], 'subtotal' => ['required', 'numeric'], 'tax_total' => ['required', 'numeric'], 'grand_total' => ['required', 'numeric'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'customer_id' => ['label' => 'Customer', 'type' => 'select', 'options' => Customer::orderBy('name')->pluck('name', 'id')->toArray()], 'order_date' => ['label' => 'Order Date', 'type' => 'date'], 'requested_delivery_date' => ['label' => 'Requested Delivery Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'approved' => 'Approved', 'partially_delivered' => 'Partially Delivered', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']], 'subtotal' => ['label' => 'Subtotal', 'type' => 'number', 'step' => '0.0001'], 'tax_total' => ['label' => 'Tax Total', 'type' => 'number', 'step' => '0.0001'], 'grand_total' => ['label' => 'Grand Total', 'type' => 'number', 'step' => '0.0001'], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
