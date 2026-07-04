<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\Warehouse;

class DeliveryOrderController extends ResourceController
{
    protected string $model = DeliveryOrder::class;
    protected string $route = 'delivery-orders';
    protected string $title = 'Delivery Order';
    protected string $viewPath = 'sales.delivery_orders';
    protected ?string $documentType = 'DO';
    protected array $with = ['customer', 'warehouse'];
    protected array $columns = ['number', 'customer.name', 'warehouse.name', 'delivery_date', 'status', 'vehicle_number'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'sales_order_id' => ['nullable', 'integer'], 'customer_id' => ['required', 'integer'], 'warehouse_id' => ['required', 'integer'], 'delivery_date' => ['required', 'date'], 'status' => ['required', 'string'], 'vehicle_number' => ['nullable', 'string'], 'driver_name' => ['nullable', 'string'], 'shipping_address' => ['nullable', 'string'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'sales_order_id' => ['label' => 'Sales Order', 'type' => 'select', 'options' => SalesOrder::orderBy('number')->pluck('number', 'id')->toArray(), 'nullable' => true], 'customer_id' => ['label' => 'Customer', 'type' => 'select', 'options' => Customer::orderBy('name')->pluck('name', 'id')->toArray()], 'warehouse_id' => ['label' => 'Warehouse', 'type' => 'select', 'options' => Warehouse::orderBy('name')->pluck('name', 'id')->toArray()], 'delivery_date' => ['label' => 'Delivery Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']], 'vehicle_number' => ['label' => 'Vehicle Number', 'type' => 'text'], 'driver_name' => ['label' => 'Driver Name', 'type' => 'text'], 'shipping_address' => ['label' => 'Shipping Address', 'type' => 'textarea'], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
