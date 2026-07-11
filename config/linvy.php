<?php

return [
    'company' => [
        'name' => 'PT Linvy Seafood Indonesia',
    ],

    'optional_modules' => ['inventory', 'purchase', 'sales', 'production', 'accounting'],

    'inventory' => [
        'reconciliation_tolerance' => 0.000001,
    ],

    'default_enabled_modules' => [
        'inventory' => true,
        'purchase' => true,
        'sales' => true,
        'production' => false,
        'accounting' => false,
    ],

    'packages' => [
        'starter' => ['label' => 'Starter', 'modules' => ['inventory', 'purchase', 'sales']],
        'standard' => ['label' => 'Standard', 'modules' => ['inventory', 'purchase', 'sales', 'production']],
        'complete' => ['label' => 'Complete', 'modules' => ['inventory', 'purchase', 'sales', 'production', 'accounting']],
    ],

    'modules' => [
        'dashboard' => ['label' => 'Dashboard', 'route' => 'dashboard', 'routes' => ['dashboard']],
        'master-data' => [
            'label' => 'Master Data',
            'items' => [
                ['label' => 'Companies', 'route' => 'companies.index', 'routes' => ['companies.*']],
                ['label' => 'Branches', 'route' => 'branches.index', 'routes' => ['branches.*']],
                ['label' => 'Warehouse Types', 'route' => 'warehouse-types.index', 'routes' => ['warehouse-types.*']],
                ['label' => 'Brands', 'route' => 'brands.index', 'routes' => ['brands.*']],
                ['label' => 'Currencies', 'route' => 'currencies.index', 'routes' => ['currencies.*']],
                ['label' => 'Payment Terms', 'route' => 'payment-terms.index', 'routes' => ['payment-terms.*']],
                ['label' => 'Taxes', 'route' => 'taxes.index', 'routes' => ['taxes.*']],
                ['label' => 'Items', 'route' => 'items.index', 'routes' => ['items.*']],
                ['label' => 'Item Categories', 'route' => 'item-categories.index', 'routes' => ['item-categories.*']],
                ['label' => 'Unit of Measure', 'route' => 'units-of-measure.index', 'routes' => ['units-of-measure.*']],
                ['label' => 'Warehouses', 'route' => 'warehouses.index', 'routes' => ['warehouses.*']],
                ['label' => 'Suppliers', 'route' => 'suppliers.index', 'routes' => ['suppliers.*']],
                ['label' => 'Customers', 'route' => 'customers.index', 'routes' => ['customers.*']],
            ],
        ],
        'purchase' => [
            'label' => 'Purchase',
            'items' => [
                ['label' => 'Purchase Requests', 'route' => 'purchase-requests.index', 'routes' => ['purchase-requests.*']],
                ['label' => 'Purchase Orders', 'route' => 'purchase-orders.index', 'routes' => ['purchase-orders.*']],
                ['label' => 'Receivings', 'route' => 'receivings.index', 'routes' => ['receivings.*']],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'items' => [
                ['label' => 'Inventory Dashboard', 'route' => 'inventory.dashboard', 'routes' => ['inventory.dashboard']],
                ['label' => 'Stock Movements', 'route' => 'stock-movements.index', 'routes' => ['stock-movements.*']],
                ['label' => 'Stock Balances', 'route' => 'stock-balances.index', 'routes' => ['stock-balances.*']],
                ['label' => 'Item Ledger', 'route' => 'item-ledger.index', 'routes' => ['item-ledger.*']],
                ['label' => 'Warehouse Transfers', 'route' => 'warehouse-transfers.index', 'routes' => ['warehouse-transfers.*']],
                ['label' => 'Stock Adjustments', 'route' => 'stock-adjustments.index', 'routes' => ['stock-adjustments.*']],
                ['label' => 'Batch Assignments', 'route' => 'batch-assignments.index', 'routes' => ['batch-assignments.*']],
            ],
        ],
        'production' => [
            'label' => 'Production',
            'items' => [
                ['label' => 'Repacking / Production Orders', 'route' => 'productions.index', 'routes' => ['productions.*']],
            ],
        ],
        'sales' => [
            'label' => 'Sales',
            'items' => [
                ['label' => 'Sales Orders', 'route' => 'sales-orders.index', 'routes' => ['sales-orders.*']],
                ['label' => 'Delivery Orders', 'route' => 'delivery-orders.index', 'routes' => ['delivery-orders.*']],
            ],
        ],
        'accounting' => [
            'label' => 'Accounting',
            'items' => [
                ['label' => 'Accounting Accounts', 'route' => 'accounting-accounts.index', 'routes' => ['accounting-accounts.*']],
                ['label' => 'Account Mapping', 'route' => 'account-mapping.index', 'routes' => ['account-mapping.*']],
            ],
        ],
        'settings' => [
            'label' => 'Settings',
            'items' => [
                ['label' => 'Users', 'route' => 'users.index', 'routes' => ['users.*']],
                ['label' => 'Roles', 'route' => 'roles.index', 'routes' => ['roles.*']],
                ['label' => 'Module Settings', 'route' => 'module-settings.index', 'routes' => ['module-settings.*']],
                ['label' => 'Document Sequences', 'route' => 'document-sequences.index', 'routes' => ['document-sequences.*']],
            ],
        ],
    ],

    'role_permissions' => [
        'super-admin' => ['*'],
        'inventory-admin' => ['dashboard', 'master-data', 'inventory', 'production'],
        'purchasing' => ['dashboard', 'master-data', 'purchase', 'inventory'],
        'sales' => ['dashboard', 'master-data', 'sales', 'inventory'],
        'production' => ['dashboard', 'master-data', 'inventory', 'production'],
        'accounting' => ['dashboard', 'master-data', 'inventory', 'purchase', 'sales', 'accounting'],
    ],
];
