# Coding Standard

Linvy ERP uses Laravel 12 with an MVC + Service Layer architecture. These standards support the enterprise, modular, inventory-first direction defined in [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md).

## Core Principles

- Build for multi-company, multi-branch, and multi-warehouse use cases.
- Keep modules loosely coupled.
- Treat Inventory as the core operational module.
- Keep Accounting optional.
- Avoid duplicated code and duplicated layouts.
- Prefer reusable components and reusable services.
- Never hardcode business logic, IDs, approval flows, warehouse behavior, or document numbers.

## Laravel Architecture

- Use Laravel 12 best practices.
- Follow MVC + Service Layer.
- Controllers must stay thin.
- Business logic belongs in Service classes.
- Validation belongs in Form Request classes where suitable.
- Models should define relationships, casts, scopes, and simple domain helpers only.
- Repository classes may be introduced later when persistence complexity justifies them.

## Controllers

- Controllers should coordinate request handling only.
- Controllers may call Form Requests, Services, and return responses.
- Controllers should not contain large transaction workflows.
- Controllers should not generate document numbers directly.
- Controllers should not update stock directly.

## Services

- Use Services for business workflows such as posting, approval, receiving, delivery, production, stock movement, and document numbering.
- Use database transactions for multi-step writes.
- Use `lockForUpdate()` for concurrent document numbering and other critical counters.
- Emit or dispatch domain events where other modules need to react.

## Validation

- Use Form Request validation where suitable, especially for complex forms and transactional documents.
- Keep validation rules close to the request boundary.
- Avoid validation logic inside Blade.

## Eloquent

- Use Eloquent relationships consistently.
- Use eager loading to prevent N+1 queries.
- Use query scopes for repeated filters.
- Use foreign keys for relational integrity.
- Use indexes for frequently queried columns and foreign keys.
- Never hardcode IDs.
- Use soft delete for master data where appropriate.
- Never remove unique constraints without a clear migration plan.

## Blade and UI

- Use Blade with TailwindCSS.
- Use reusable Blade components for common UI.
- Use consistent buttons.
- Use consistent badge colors.
- Reuse the Status Badge component for status display.
- Do not put business logic inside Blade.
- Do not query models repeatedly inside Blade.
- Move options, counts, and dataset preparation to controllers or services.
- Do not duplicate layouts.

## Seeders

- Seeder data must look realistic.
- Seeder logic must be idempotent using `updateOrCreate`.
- Never truncate master tables in normal seeders.
- Never use placeholder records such as `Supplier A`, `Item 1`, or `Warehouse 1`.
- Keep demo data aligned with PT Linvy Seafood Indonesia, Surabaya, and the default warehouse/item structure defined in project rules.

## Inventory Rules

- Stock-affecting transactions must create Stock Movement.
- Stock Balance must be derived or maintained from Stock Movement.
- Never update stock directly from controllers.
- Receiving, warehouse transfer, production, sales delivery, and stock adjustment must flow through Stock Movement.
- Warehouse is selected per receiving line, not in the receiving header.

## Accounting Rules

- Accounting must consume transaction data.
- Accounting must not control inventory.
- Inventory must not depend on Journal Entry.
- The ERP must continue working when the Accounting module is disabled.

## Document Number Rules

- Use DocumentSequenceService for document numbers.
- Do not generate numbers inside controllers.
- Support daily, monthly, and yearly sequence periods.
- Monthly is the default sequence period.
- Use transactions and row locking for concurrent number generation.
