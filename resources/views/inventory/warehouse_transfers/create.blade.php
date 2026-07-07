@include('inventory.warehouse_transfers._form', [
    'action' => route('warehouse-transfers.store'),
    'method' => 'POST',
])
