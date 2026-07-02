# PROJECT_ARCHITECTURE.md

## Application Name

Linvy ERP

## Purpose

ERP sederhana untuk distributor, inventory multi warehouse, purchase, production/repacking, sales, dan accounting foundation.

## Inventory Concept

Stock is not stored directly in item master.

Inventory flow:
Item → Stock Movement → Stock Balance

## Main Tables

- items
- item_categories
- warehouses
- units
- suppliers
- customers
- stock_movements
- stock_balances
- purchase_orders
- purchase_order_lines
- receivings
- receiving_lines
- warehouse_transfers
- stock_adjustments
- production_orders
- production_inputs
- production_outputs
- sales_orders
- delivery_orders

## Accounting Foundation

Item Category contains default accounts:

- Inventory Account
- COGS Account
- Sales Account
- Purchase Account
- WIP Account
- Adjustment Account
- Waste Account

Item can override default accounts if needed.

## Important Rule

Every stock change must create a stock movement.
