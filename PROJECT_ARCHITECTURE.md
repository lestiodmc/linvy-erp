# PROJECT_ARCHITECTURE.md

## Product Name

Linvy ERP

## Purpose

Linvy ERP is a Laravel 12 ERP application for master data, inventory, purchase, sales, and operational workflows. The application must stay modular so the core app can run without optional packages such as accounting.

## Package Concept

- Starter: Master Data, Inventory, Purchase, Sales
- Standard: Starter + Production / Repacking
- Complete: Standard + Accounting

## Core Modules

Core modules are modules that belong to the main application and must work without the accounting package:

- Master Data
- Inventory
- Purchase
- Sales
- Reports
- Settings

Production / Repacking is included in the Standard package.

## Optional Accounting Module

Accounting is an optional module/package and is not mandatory for the core application.

Rules:

- The core app must run without the accounting module.
- Accounting account mapping can be added by package or feature flag when needed.
- Do not create automatic journal entries unless requested.
- Existing operational modules must not depend on accounting tables to perform their core workflows.

## Inventory Concept

Stock quantity is not stored directly in the item master.

Inventory flow:

Item -> Stock Movement -> Stock Balance

Rules:

- Every stock change must create a stock movement.
- Stock balances are derived from stock movements.
- Database structure must remain scalable for multi warehouse inventory.
- Stock transactions should keep item, warehouse, movement type, document reference, quantity, and transaction date.

## Document Sequence

Use monthly document sequence by default.

Default format:

- Purchase Order: PO/YYYY/MM/0001
- Receiving: RCV/YYYY/MM/0001
- Sales Order: SO/YYYY/MM/0001
- Delivery Order: DO/YYYY/MM/0001
- Warehouse Transfer: TRF/YYYY/MM/0001
- Stock Adjustment: ADJ/YYYY/MM/0001
- Production / Repacking: PRD/YYYY/MM/0001

## View Folder Structure

Blade views must follow a module-based folder structure.

Examples:

- resources/views/master/items/index.blade.php
- resources/views/master/suppliers/index.blade.php
- resources/views/inventory/stock_movements/index.blade.php
- resources/views/inventory/stock_balances/index.blade.php
- resources/views/purchase/purchase_orders/index.blade.php
- resources/views/purchase/receivings/index.blade.php
- resources/views/sales/sales_orders/index.blade.php
- resources/views/sales/delivery_orders/index.blade.php
- resources/views/production/production_orders/index.blade.php
- resources/views/settings/document_sequences/index.blade.php

Reusable components should be limited to common UI parts such as:

- Buttons
- Cards
- Badges
- Tables
- Form inputs
- Alerts
- Empty states

## Sidebar Navigation

The sidebar should use a dropdown/collapsible menu concept grouped by module.

Expected groups:

- Dashboard
- Master Data
- Inventory
- Purchase
- Sales
- Production / Repacking
- Reports
- Settings
- Accounting, only when the optional accounting module is enabled

## Theme

Future theme customization is planned but not implemented yet.

For now, keep the UI enterprise, clean, modern, and consistent across modules.

