@include('inventory.stock_adjustments._form', [
    'action' => route('stock-adjustments.store'),
    'method' => 'POST',
])
