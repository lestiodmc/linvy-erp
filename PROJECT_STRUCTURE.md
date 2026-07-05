# Project Structure

This document explains the main folder responsibilities for Linvy ERP. Architectural rules and module behavior are defined in [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md).

## `app/Models`

Contains Eloquent models and relationships.

Models should define:

- Fillable fields
- Casts
- Relationships
- Simple scopes
- Simple domain helpers

Models should not contain large transactional workflows.

## `app/Http/Controllers`

Contains HTTP controllers grouped by application area.

Controllers should:

- Receive requests
- Delegate validation
- Call Services for business workflows
- Return views or redirects

Controllers should stay thin and should not contain stock posting, approval, or document numbering logic.

## `app/Services`

Contains business workflow services.

Use this folder for:

- Document numbering
- Approval processing
- Receiving posting
- Stock Movement creation
- Stock Balance maintenance
- Warehouse transfer posting
- Sales delivery posting
- Production posting
- Future accounting event processing

## `app/Repositories` Future

Reserved for a future repository layer when query complexity grows.

Do not introduce repositories prematurely. Prefer Eloquent relationships, scopes, and Services until a repository clearly improves maintainability.

## `resources/views/master`

Contains Blade views for Master Data modules.

Examples:

- Companies
- Branches
- Warehouse Types
- Warehouses
- Item Categories
- Units of Measure
- Brands
- Items
- Customers
- Suppliers

Master views should reuse shared resource layouts and Blade components where possible.

## `resources/views/purchase`

Contains Blade views for Purchasing workflows.

Examples:

- Purchase Requests
- Purchase Orders
- Receivings

Purchase views should support document status flow, approval, and line-based transaction entry.

## `resources/views/inventory`

Contains Blade views for Inventory workflows.

Examples:

- Stock Movements
- Stock Balances
- Warehouse Transfers
- Stock Adjustments
- Stock Opname in the future

Inventory views must respect the rule that stock changes are represented by Stock Movement.

## `resources/views/sales`

Contains Blade views for Sales workflows.

Examples:

- Sales Orders
- Delivery Orders
- Future invoices

Sales delivery must create Stock Movement through Services.

## `resources/views/accounting`

Contains Blade views for the optional Accounting module.

Accounting must remain optional. Inventory workflows must continue working when Accounting is disabled.

## `resources/views/shared`

Contains shared Blade templates and reusable resource views.

Use this folder for common:

- Resource indexes
- Resource forms
- Resource detail pages
- Field rendering partials
- Shared UI patterns

Shared views must not include parent resource views recursively.

## `database/migrations`

Contains database schema changes.

Migrations should:

- Use foreign keys
- Use indexes
- Preserve unique constraints unless a deliberate migration plan exists
- Support multi-company, multi-branch, and multi-warehouse growth

## `database/seeders`

Contains seeders for master data and demo data.

Seeders should:

- Use realistic data
- Use `updateOrCreate`
- Avoid truncating master tables
- Avoid hardcoded IDs

## `docs` Future

Reserved for future detailed documentation.

Possible future documents:

- Module specifications
- API documentation
- Database diagrams
- Posting flow diagrams
- Approval engine specification
- Deployment guide
