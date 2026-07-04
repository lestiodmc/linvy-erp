# AGENTS.md

## Project

Project name: Linvy ERP

Stack:

- Laravel 12
- MySQL
- Blade

## Main Rules

- Use Laravel 12 conventions.
- Use Blade for UI.
- Use MySQL as database.
- Accounting module is an optional package, not mandatory.
- Core app must run without the accounting module.
- Do not store stock quantity directly in the items table.
- All stock changes must use stock_movements.
- Use monthly document sequence by default.
- View structure must follow module-based folder structure, for example:
  - resources/views/master/items/index.blade.php
  - resources/views/purchase/purchase_orders/index.blade.php
- Use reusable components only for UI parts like buttons, cards, badges, and tables.
- Do not create automatic journal entries unless requested.
- Keep UI enterprise, clean, modern, and consistent.

## Main Modules

- Master Data
- Purchase
- Inventory
- Production / Repacking
- Sales
- Reports
- Settings

