# AGENTS.md

## Project

Linvy ERP - Laravel 12 + MySQL ERP application.

## Rules

- Use Laravel 12 conventions.
- Use Blade for UI.
- Use MySQL as database.
- Do not store stock quantity directly in items table.
- All stock transactions must be recorded in stock_movements.
- Use item category accounting as default account mapping.
- Item may override accounting account if needed.
- Keep database structure scalable for multi warehouse inventory.
- Do not create journal entries automatically yet unless requested.

## Main Modules

- Master Data
- Purchase
- Inventory
- Production / Repacking
- Sales
- Reports
- Settings
