# AGENTS.md

This document defines coding standards for Linvy ERP.

Every AI agent (Codex, ChatGPT, Copilot, etc.) MUST follow these rules.

---

# General Rules

Never hardcode business logic.

Always build scalable code.

Always think Enterprise first.

Always prefer reusable components.

Never duplicate code.

---

# UI Rules

Use TailwindCSS.

Create reusable Blade Components.

Use consistent buttons.

Use consistent badge colors.

Status Badge component must be reused.

Do not duplicate layouts.

---

# Inventory Rules

Inventory is the core module.

Never update stock directly.

Every inventory transaction must create Stock Movement.

Stock Balance updates automatically.

Accounting never updates stock.

---

# Warehouse Rules

Warehouse belongs to Branch.

Branch belongs to Company.

Warehouse has Warehouse Type.

Item stores Default Warehouse Type.

Receiving chooses Warehouse per line.

Warehouse Header is prohibited.

---

# Accounting Rules

Accounting is optional.

ERP must work without Accounting Module.

Never make Inventory depend on Journal.

Accounting listens to Inventory events.

---

# Service Rules

Business logic belongs inside Services.

Controllers should be thin.

Validation belongs inside Form Request.

Never write large Controllers.

---

# Database Rules

Always use Foreign Keys.

Always use Indexes.

Always use Transactions.

Use lockForUpdate() for document numbering.

Never remove unique constraints.

---

# Document Number Rules

Use DocumentSequenceService.

Never generate numbers inside Controllers.

Support:

Monthly

Daily

Yearly

Default:

Monthly.

---

# Approval Rules

Approval Level must be configurable.

Do not hardcode approval flow.

---

# Stock Rules

Receiving

↓

Stock Movement

↓

Stock Balance

Warehouse Transfer

↓

Stock Movement

↓

Stock Balance

Production

↓

Stock Movement

↓

Stock Balance

Sales Delivery

↓

Stock Movement

↓

Stock Balance

---

# Coding Style

Follow PSR-12.

Use Laravel Best Practices.

Avoid duplicate queries.

Use eager loading.

Use Form Request validation.

Prefer Service classes.

Prefer Blade Components.

---

# Seeder Rules

Seeder data must look realistic.

Never use:

Supplier A

Item 1

Warehouse 1

Use realistic company names.

---

# Demo Data

Default Company

PT Linvy Seafood Indonesia

Default Branch

Surabaya

Default Warehouses

Raw Material Warehouse

Packaging Warehouse

Production Warehouse

Finished Goods Warehouse

QC Warehouse

Transit Warehouse

Reject Warehouse

Default Items

Raw Material

Packaging

Finished Goods

Consumable

---

# Long Term Principle

Every new feature must answer:

Can this work for:

Multiple Companies?

Multiple Branches?

Multiple Warehouses?

Future Locations?

Future Accounting?

If not,

Refactor before implementing.
