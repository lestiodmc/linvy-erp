@php
    $columnLabels = [
        'sku' => 'Code',
        'name' => 'Name',
        'category.name' => 'Category',
        'brand.name' => 'Brand',
        'item_type' => 'Type',
        'baseUnit.code' => 'Base UOM',
        'is_active' => 'Active',
    ];
@endphp

@include('shared.resources.index')
