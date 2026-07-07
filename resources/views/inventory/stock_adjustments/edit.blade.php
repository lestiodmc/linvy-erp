@include('inventory.stock_adjustments._form', [
    'action' => route('stock-adjustments.update', $record),
    'method' => 'PUT',
])
