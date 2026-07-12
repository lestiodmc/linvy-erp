# Linvy ERP Production Architecture

## 1. Purpose

This document defines the production foundation that should be approved before implementation. It is grounded in the current Laravel 12 codebase as audited on 12 July 2026. It targets seafood processing, repacking, distribution, and light manufacturing; it deliberately excludes heavy MRP, finite-capacity scheduling, mandatory accounting, and automatic cross-category UOM conversion.

Production must remain inventory-first. Controllers may coordinate requests, but Production services must own workflow rules and must call the Inventory posting layer for every stock effect. Accounting, when enabled later, listens to posted Production events; Inventory must never depend on journals.

## 2. Business Scope

Phase-one operations cover STANDARD transformation and REPACKING. Both can consume multiple materials from warehouse-specific batches and receive one main output through partial executions. The architecture leaves explicit extension points for by-products, waste, genealogy, QC, costing, and planning.

```text
Active BOM/Formula -> Production Order snapshot -> Release
                                           |
                         Material Issue(s) -- OUT movements
                                           |
                   Finished Goods Receipt(s) -- IN movements
                                           |
                         Complete -> Close
```

The design supports multiple companies and branches. Every operational document belongs to one company and branch. Warehouses remain line-specific where execution requires it; there is no generic warehouse header that overrides line warehouses.

## 3. Current System Audit

### Items and units

`App\Models\Item` currently exposes `type`, `item_type`, `track_inventory`, `is_stock_item`, `allow_negative_stock`, `is_batch_tracked`, `is_serial_tracked`, `has_expiry_date`, `standard_cost`, and `cost_method` (`standard`, `average`, `fifo`). It relates to legacy `unit_of_measure_id`, authoritative `base_unit_id`, purchase and sales units, a default warehouse type, and optional Accounting account fields including WIP and waste accounts. There is no reliable conversion service: `InventoryPostingService::baseQuantity()` currently returns the submitted quantity even when units differ. Phase one must therefore post in the item base UOM and reject unsupported conversion.

### Warehouses and access

`Warehouse` belongs to both Company and Branch and has a Warehouse Type. `WarehouseSeeder` includes Raw Material, Packaging, Production, Finished Goods, QC, Transit, and Reject concepts. Existing migrations enforced branch/warehouse structure. Production must validate that every issue/receipt warehouse belongs to the order company and branch and that the user can access that branch. Warehouse type is a default/policy hint, not a substitute for ownership checks.

### Inventory persistence

| Store | Current role | Production use |
|---|---|---|
| `stock_movements` | Immutable audit rows with company, branch, warehouse, item, UOM/base UOM, type/source, IN/OUT, batch, serial, expiry and cost | One row per posted production execution line (or serial) |
| `stock_balances` | Company/branch/warehouse/item balance with base UOM, on-hand, reserved, available, average/last cost | Decrease on issue, increase on receipt |
| `stock_batch_balances` | Warehouse/item/batch/expiry balance | Decrease input batch; increase output batch |

`InventoryPostingService` is the reusable core. It validates tracked items, required batch/expiry/serial data, negative-stock rules, and warehouse/item existence; creates movements; locks and updates balance rows; and updates batch balances. Receiving already uses an outer transaction, locks its source document, checks prior movements, then posts. Warehouse Transfer, Stock Adjustment, and Batch Assignment services provide useful document-service patterns.

Important gaps before Production execution:

- `createMovement()` and `updateStockBalance()` are public primitives, but there is no atomic multi-line generic posting command or production-specific idempotency contract.
- Idempotency for Receiving is document/type based, not source-line/event based.
- UOM conversion is intentionally incomplete.
- Reversal is not generalized.
- Serial issue selection and serial availability need an explicit execution design if Production supports serial materials.

### Ledger and traceability

`InventoryLedgerService` reads Stock Movements, preserves item/warehouse/batch contexts, and resolves source links by transaction type. Production types and `InventoryMovementSource` mappings will need extension so ledger and command-palette navigation open the issue or receipt document.

### Numbering

`DocumentSequenceService` is the required concurrency-safe mechanism. It normalizes aliases, selects branch/company/global sequences, wraps generation in a transaction, locks counters with `lockForUpdate()`, supports monthly/yearly/never periods, and handles duplicate keys. It already aliases `PRD`/`production` to `PRODUCTION_ORDER`. Separate sequence codes are required for BOM, Material Issue, and Finished Goods Receipt.

### Security, modules, and permissions

`module:production` gates the existing resource routes. User roles currently grant module names rather than action permissions (`production`, `inventory`, etc.), with `super-admin` using `*`. Branch access is enforced independently in modern Inventory controllers. Production needs granular permissions without weakening module and branch checks.

### Existing Production scaffold

The current implementation is a placeholder:

- Migration `2026_07_02_000006_create_production_repacking_tables.php` creates `productions`, `production_inputs`, and `production_outputs`.
- `productions` has a globally unique number, two warehouses, an enum restricted to `repacking`, and `draft/posted/cancelled` enum status. It lacks company, branch, BOM, plan/actual quantities, batch/expiry execution, audit actors, partial documents, idempotency, and reversal metadata.
- Input/output lines contain planned-looking quantity and unit cost but no posting/source-line identity or batch data.
- `ProductionController` is a generic `ResourceController`; it accepts user-supplied status and has no posting service.
- `Production` only relates to the two warehouses and input/output lines.
- Routes expose a generic resource; navigation labels it “Repacking / Production Orders”; Quick Action says “New Repacking Order.”
- Production is disabled by default, enabled in Standard/Complete packages, and does not depend on Accounting.

This scaffold must not be used to post stock. A future implementation migration should deliberately migrate, replace, or retire it; do not silently reinterpret posted legacy records.

### Database compatibility

New statuses/types should be strings validated by application constants/Form Requests, not database enums. Use foreign keys, explicit indexes, decimal `(18,6)` quantities, portable SQL, and avoid vendor-specific generated columns. Unique constraints and transaction locking must work on MySQL and PostgreSQL.

## 4. Terminology

| User-facing term | Meaning |
|---|---|
| Production Formula (BOM) | Versioned recipe for one main finished item |
| Production Order | Planning and control document; never call it Work/Manufacturing Order |
| Material Issue | Posted material consumption from source warehouse |
| Finished Goods Receipt | Posted usable output into destination warehouse |
| By-product | Additional usable inventory output |
| Waste | Non-usable loss recorded operationally in phase one |
| Yield | Actual main output divided by planned main output |
| WIP | An order with issued materials not yet operationally completed |
| Production Batch | Batch identifier assigned to an output receipt |

## 5. Production Types

Use one `production_type` string: `STANDARD` or `REPACKING`. Both use the same Formula structure, Production Order, issue/receipt documents, posting coordinator, and statuses. UI presets differ: Repacking emphasizes source SKU, packaging, labels, and output pack; Standard emphasizes formula materials. Separate code paths would duplicate stock controls and traceability without a business benefit.

## 6. Process Flow

1. Create a draft Formula and activate an immutable version.
2. Create a Production Order from the active Formula. Copy header and lines as a snapshot.
3. Release after validating ownership, dates, active items, base UOMs, and warehouses. No stock effect and no reservation in phase one.
4. Post one or more Material Issues. Each is atomic and reduces source/batch balances.
5. Post one or more Finished Goods Receipts. Each increases destination/batch balances.
6. Complete when the main output target and required issue rules are satisfied; allow partial executions before completion.
7. Close administratively. Posted documents remain immutable.

## 7. Document Workflows

### Production Order status definitions

| Status | Meaning | Editable |
|---|---|---|
| `draft` | Planning only; no stock effect | Header and snapshots |
| `released` | Approved for execution; no reservation/stock effect | Notes/dates only by controlled action |
| `in_progress` | At least one posted issue or receipt | No quantity-plan edits |
| `completed` | Completion rules met; no normal execution | No |
| `closed` | Administratively final | No |
| `cancelled` | Cancelled before stock effect, or after a future full controlled reversal | No |

| Current | Action | Next | Inventory effect | Notes |
|---|---|---|---|---|
| draft | edit | draft | none | Recalculate snapshots only by explicit refresh |
| draft | release | released | none | Lock order + validate Formula snapshot |
| draft | cancel | cancelled | none | Safe |
| released | post first issue/receipt | in_progress | line-specific OUT/IN | Posting service owns transition |
| released | cancel | cancelled | none | Only if no posted execution |
| in_progress | post issue/receipt | in_progress | OUT/IN | Remaining-quantity checks |
| in_progress | complete | completed | none | Validate completion policy |
| completed | close | closed | none | Administrative |
| released/in_progress/completed | reverse | same until all reversed | opposite movements | Deferred from phase one |

Phase one must prohibit cancellation after any posted execution. Do not implement “delete and restore stock.”

### Formula statuses

`draft`, `active`, `inactive`, `obsolete`. Only draft is editable. Activation validates lines and dates. Active versions become immutable; changes require a new version.

### Issue/receipt statuses

Use `draft`, `posted`, `cancelled`. Only unposted draft documents are editable/cancellable. Posted documents are immutable. Posted cancellation is unavailable until reversal exists.

## 8. Proposed Data Model

All quantities use `decimal(18,6)`. All business tables have timestamps and `created_by`/`updated_by`; posting documents also have `posted_by`/`posted_at`. Foreign keys use restrictive deletes for referenced masters and documents. Historical snapshot lines must not cascade from Item/UOM deletion.

| Table | Purpose and key columns | Constraints/indexes |
|---|---|---|
| `production_boms` | `company_id`, nullable `branch_id`, `number`, `name`, `production_type`, `finished_item_id`, `base_output_quantity`, `output_uom_id`, default source/destination warehouses, `version`, effective dates, `status`, notes, activation audit | Unique `(company_id, number)` and `(company_id, finished_item_id, version)`; indexes status/effective dates; check quantity > 0 in service |
| `production_bom_materials` | BOM, item, quantity, base UOM, `quantity_type`, source warehouse override, sequence, optional future scrap percent, notes | Unique `(production_bom_id, sequence)`; index item; restrict deletes |
| `production_bom_outputs` | BOM output definitions: one `MAIN`, optional future `BY_PRODUCT`; item, quantity, UOM, destination override, sequence | Unique one MAIN per BOM via service plus `(bom, sequence)`; waste is not an inventory output in phase one |
| `production_orders` | Company/branch, number, type, BOM/version reference, copied formula identity, finished item, planned qty/base UOM, dates, status, release/completion/close audit, notes | Unique `(company_id, number)`; indexes branch/status/dates/BOM; optimistic or locked transitions |
| `production_order_materials` | Immutable plan snapshot: source BOM line, item, planned quantity, actual issued accumulator, base UOM, default source warehouse, tracking flags snapshot, sequence, notes | Unique `(order, sequence)`; indexes item/warehouse; actual is maintained only by posting service |
| `production_order_outputs` | Snapshot: item, `output_type`, planned/actual received, base UOM, destination warehouse, tracking flags, sequence | Exactly one MAIN in phase one; unique `(order, sequence)` |
| `production_material_issues` | Company/branch/order, number, issue date, status, posting/idempotency/reversal audit, notes | Unique `(company_id, number)`; indexes order/status/date |
| `production_material_issue_lines` | Issue, order-material snapshot, item, warehouse, quantity/base quantity, UOM/base UOM, batch, expiry, serial when supported, sequence, notes | Unique `(issue, sequence)`; index order material and warehouse/item/batch |
| `production_receipts` | Company/branch/order, number, receipt date, status, posting/idempotency/reversal audit, notes | Unique `(company_id, number)`; indexes order/status/date |
| `production_receipt_lines` | Receipt, order-output snapshot, item, warehouse, quantity/base quantity, UOM/base UOM, batch, manufacture date, expiry, output type, sequence, notes | Unique `(receipt, sequence)`; index order output and warehouse/item/batch |

Do not soft-delete posted execution documents or active/historical Formula versions. Draft masters/documents may use explicit archive status; auditability is clearer than soft deletion. If legacy `productions` data exists, migration planning must inventory status/usage and preserve it in a read-only legacy view or map it explicitly.

## 9. BOM Architecture

Use one main output plus optional by-products as the long-term model. Phase one implements exactly one MAIN output and material lines; by-product rows may be schema-ready but hidden until posting/test rules exist. Multiple equal co-products would complicate scaling, completion, and costing.

For proportional material:

```text
required = BOM material quantity * order planned output / BOM base output quantity
```

For fixed material, `required = BOM material quantity`. Calculate with decimal strings/BCMath or a tested decimal value object; never binary-float intermediate values for persistence. Store six decimals, calculate at higher internal precision, then round once to six decimals using an agreed half-up policy. Phase one requires material/output UOM to equal the item base UOM. A planned-quantity edit in draft recalculates snapshots; after release it is prohibited.

Version rules:

- Version is an integer scoped to company + finished item.
- At most one effective active version for a company/branch/date context, enforced in the activation transaction and tests.
- Branch-null means company-wide; a branch-specific Formula may override it.
- Effective periods may not overlap for the same applicability and finished item.
- A Production Order stores `bom_id`, version, copied item/quantity/UOM/warehouse/tracking values. Historical orders never reread current Formula lines.

## 10. Production Order Architecture

Creation service selects an accessible active Formula, locks/validates it, generates `PRODUCTION_ORDER`, and creates the order plus snapshots in one transaction. Manual orders without a Formula are deferred. The order header supplies planning defaults only; each snapshot/execution line owns its warehouse to comply with Linvy’s no-warehouse-header inventory rule.

Release service locks the order, checks `draft`, verifies same-company/branch warehouse ownership, active/tracked items, one main output, positive planned quantities, and base UOM agreement, then records actor/time. It emits no Inventory or Accounting effect.

Actual accumulators are convenience fields updated from posted lines under lock. Posted issue/receipt lines remain the audit source of truth; reconciliation tests compare accumulators against their sums.

## 11. Material Issue Architecture

Each issue references one released/in-progress order and may cover part of one or more remaining material requirements. Multiple issue documents are allowed. Each line selects an accessible source warehouse and, for tracked materials, the actual batch/expiry or serial.

Initial rules:

- Quantity is positive and in base UOM.
- Issue cannot exceed remaining planned material. No override in phase one.
- Negative stock follows the Item flag and existing `InventoryPostingService` validation.
- Batch/expiry/serial requirements reuse Item flags and current stock checks.
- A warehouse must belong to the order company/branch; several source warehouses may be used through separate lines.
- Post locks issue, order, relevant order-material rows, and inventory balances; validates status and remaining quantity; posts all lines atomically; updates accumulators/status; records actor/time.

Movement type: add a normalized constant such as `PRODUCTION_MATERIAL_ISSUE` with direction `OUT`. `transaction_id` points to the issue header, `reference_type`/`reference_id` identify it, and a source-line identity is required for idempotency.

## 12. Finished Goods Receipt Architecture

Receipts reference one released/in-progress order and support partial receipt and multiple receipt documents. Phase-one order may produce multiple receipts but one finished batch per receipt; therefore one order can produce several batches in a controlled, traceable manner.

For a batch-tracked main output, batch is required. Expiry is required when the Item flag requires it. Phase one uses a manually entered validated expiry date; manufacture-date-plus-shelf-life calculation is deferred until an Item shelf-life field/policy exists. Output is received in base UOM to an accessible same-company/branch destination warehouse. Multiple destination warehouses are allowed through separate receipt lines only if approved; recommended default is one configured destination per order.

Movement type: `PRODUCTION_FINISHED_GOODS_RECEIPT`, direction `IN`. Posted receipts are immutable and create linked Stock Movement, Stock Balance, and Stock Batch Balance effects atomically.

## 13. Batch and Expiry Traceability

Phase one can trace output batch -> receipt -> Production Order -> all posted issue lines -> input batches, and reverse by querying issue order then its receipts. This is sufficient for recall reporting if every execution line is immutable and indexed.

Add `production_batch_genealogy` only when allocation must be exact (for example, input batch A was used only for output batch X while the same order also produced Y). Recommended future columns: order, issue line, receipt line, input batch/expiry, output batch/expiry, allocated base quantity. Do not infer exact allocation when an order has several input and output batches; label order-level genealogy accordingly.

## 14. WIP

Operational WIP is a set of released/in-progress orders with execution progress—not one quantity. Inquiry columns: order, finished item, planned output and its UOM, material-line completion percentages, main-output receipt percentage, warehouses, status, planned dates, and elapsed/overdue days. Material progress is evaluated per material line; never sum KG, PCS, BOX, or LTR. Accounting WIP valuation is outside phase one.

## 15. By-products and Waste

By-product is a usable inventory receipt with its own Item, UOM, warehouse, batch/expiry rules, and IN movement. Defer it from phase one unless it is essential; the output structure is ready for it.

Waste should initially be a non-stock informational line/quantity per material UOM with reason and notes. This avoids fake inventory and mandatory reject warehouses. Later, a configured stock-tracked waste Item and Waste/Reject Warehouse may create an IN movement when the business actually stores/sells waste. Neither option automatically creates journals.

Yield and variance:

- Main output yield `% = actual main output / planned main output * 100`.
- Material variance is `actual issued - planned` per material and UOM.
- Phase one shows order/item-specific yield and per-line variance. Cross-UOM totals and monetary variance wait for costing.

## 16. Inventory Posting

Create a Production execution service that coordinates documents and calls a hardened Inventory posting command; never update balances in controllers.

```text
DB transaction
  lock execution header + order + snapshot lines
  assert draft/not previously posted and ownership/access
  validate remaining quantities and tracking
  for each line:
    InventoryPostingService.createMovement(payload)
    InventoryPostingService.updateStockBalance(payload)
  update actual accumulators and order status
  mark document posted; dispatch after-commit domain event
```

Before reuse, prefer adding one atomic inventory method accepting a source document/event and line payloads, so movement and balances cannot be called separately accidentally. It must preserve current validation and row locks. Source payload includes company, branch, line warehouse, item, submitted/base UOM and quantities, batch/serial/expiry, transaction type/header identity/number/date, source line identity, remarks, and actor.

Material Issue decreases Stock Balance and selected Stock Batch Balance. Receipt increases both. Batch reconciliation remains the invariant. Accounting may subscribe after commit but failure/absence of Accounting must not prevent operational posting.

## 17. Idempotency and Reversal

Required protections:

- Lock the execution header and require `draft`.
- A unique posting key per event/source line, preferably a new movement column/unique constraint such as `(source_type, source_line_id, posting_event)`. If schema naming follows current fields, use deterministic `transaction_type + reference_type + reference_id + source_line_id`.
- Unique document number scoped as designed.
- One database transaction, balance row locks, posted actor/time, and after-commit events.
- Retry returns a controlled “already posted” result; it never duplicates stock.

Reversal must create opposite movements linked to originals; never edit/delete posted movements. Receipt reversal must verify finished stock is still available in the same warehouse/batch. Issue reversal creates IN. Reversal should reverse whole documents initially and lock original/reversal documents. Because this is complex, defer reversal from phase one and prohibit cancellation of any order with posted execution; allow completion/close only.

## 18. Security and Access

Recommended permissions:

- `production.bom.view/create/edit/activate`
- `production.order.view/create/edit/release/complete/close`
- `production.material_issue.view/create/post`
- `production.receipt.view/create/post`
- `production.report.view`

Granular permission checks supplement `module:production`; they do not replace branch scope. Every query scopes company/branch via the authenticated user’s accessible branches. Form Requests validate IDs, and services revalidate ownership under lock to prevent forged requests. Permission visibility, route authorization, and warehouse access are separate controls.

## 19. UI/UX Structure

Reuse existing Enterprise components: page headers/actions, filter panels, data tables/pagination, enterprise forms and line tables, searchable selects, status badges, source-document cards, sticky action bars, Appearance tokens/density, confirmation modal, and Ctrl+K registry.

Recommended phase-one navigation:

```text
Production
  Setup
    Production Formulas
  Planning
    Production Orders
  Execution
    Material Issues
    Finished Goods Receipts
  Inquiries
    Work in Progress
    Production History
```

Batch Traceability can enter phase five. Reports remain hidden until implemented. Order displays its Formula source card (number, finished item, version/status, View Formula). Issue and Receipt display a Production Order source card. Status remains semantic, never accent-derived.

## 20. Reporting

Safe dashboard KPIs are counts: Draft, Released, In Progress, Overdue, Completed Today, pending issue/receipt orders, and output batch count. Charts use order counts or selected-item/UOM yield. Never show mixed-UOM production or WIP totals. Initial inquiries include WIP, history, and order-level batch traceability; later reports cover consumption and variance per item/UOM.

## 21. Costing Boundary

Phase one preserves current Inventory costing behavior and records quantities/cost fields needed for future work. Options later include standard output cost, actual issued material cost, weighted average, packaging, labor, and overhead. No Production service calls a journal. An optional Accounting listener may consume immutable posted issue/receipt events and map Item WIP/waste accounts when the module is enabled.

## 22. Implementation Phases

| Phase | Deliverables | Tests and principal risks |
|---|---|---|
| 1 Formula | Migration strategy for legacy tables; Formula/version tables, models, Form Requests, services, controllers, routes/permissions, Enterprise views | Version/effective overlap, activation immutability, ownership, MySQL/PostgreSQL |
| 2 Order | Order/snapshot tables; requirement calculator; create/release services; controllers/routes/forms/source cards | Decimal scaling, snapshot independence, concurrent release, unsupported UOM |
| 3 Issue | Issue tables; posting service extension; item/batch selectors; issue list/form/show; source links | Partial/multiple issue, stock/batch locks, negative prevention, idempotency, over-issue |
| 4 Receipt | Receipt tables; IN posting; output batch/expiry UI; source links | Partial/multiple receipt, batch creation, expiry, idempotency, over-production |
| 5 Completion/inquiries | Completion service, WIP/history, order-level genealogy, dashboard counts | Completion races, mixed-UOM safety, access, pagination/filter retention |
| 6 Extensions | By-products, waste policy, exact genealogy, variance/costing, reversal, optional Accounting/QC listeners | Allocation ambiguity, receipt reversal availability, valuation and event retry |

Each phase includes migration rollback strategy, models/relations, service tests, thin controllers, Form Requests, policy/permission tests, navigation/command palette only for delivered screens, and build/browser validation.

## 23. Test Strategy

Minimum automated coverage:

- Proportional/fixed requirements, six-decimal rounding, zero/invalid quantities, incompatible UOM rejection.
- Formula activation/effective overlap, immutable active version, new version, snapshot unaffected by later Formula changes.
- Company/branch/warehouse query and service authorization, including forged warehouse IDs.
- Partial and multiple issues; remaining requirement; over-issue rejection; concurrent issue race.
- Negative-stock policy; batch/expiry/serial validation; batch-level insufficiency.
- Partial and multiple receipts; required output batch/expiry; over-production policy.
- Idempotent retries and unique source-line posting; document and balance row locks.
- Order status transition matrix and completion rule.
- No cancellation after posting; future reversal produces linked opposite movements exactly once.
- Genealogy forward/reverse at order level and later exact allocation.
- WIP/dashboard mixed-UOM safety.
- Source links and Item Ledger visibility.
- Production works with Accounting disabled; listener integration is optional.
- Same feature suite against MySQL and PostgreSQL in CI.

## 24. Risks

| Risk | Mitigation |
|---|---|
| Legacy Production scaffold mistaken for posted architecture | Explicit migration/data audit; never attach posting to generic controller |
| Duplicate or partial posting | Atomic coordinator, header/line locks, unique posting keys |
| UOM corruption | Base-UOM-only phase one; no silent conversion |
| Cross-branch stock access | Query scope plus locked service ownership validation |
| Batch recall ambiguity with several output batches | Clearly order-level genealogy; add allocation table when exact mapping is required |
| Race on remaining planned or stock | Lock order snapshots and inventory balances in consistent order |
| Reversal consumes unavailable finished stock | Defer reversal; prohibit posted cancellation |
| Mixed-UOM reporting | Counts and per-item/UOM metrics only |
| Accounting coupling | After-commit optional listeners; no journal dependency |

## 25. Open Decisions

| Decision | Recommended default |
|---|---|
| Several finished batches per order? | Yes, one batch per receipt; multiple receipts allowed |
| Over-issue? | No in phase one |
| Over-production? | No in phase one; add tolerance/permission later |
| Material reservation now? | Later |
| Waste handling? | Informational, non-stock in phase one |
| By-products phase one? | No unless launch-critical |
| QC before availability? | Later; initial receipt is available immediately |
| Finished expiry determination? | Manual required date when Item requires expiry; shelf-life rule later |
| Several source warehouses? | Yes, line-specific and same branch/company |
| Several output warehouses? | Default one; permit line-specific same-branch destinations only if approved |
| Manual order without Formula? | No in phase one |
| Partial completion? | Partial execution yes; completion is an explicit final action after rules pass |
| Completion material rule? | Require no planned material shortage and main output target reached; user approval required for tolerances |
| Serial-tracked Production? | Defer unless required; batch/expiry first |

## 26. Recommended Phase-One Scope

Approve a deliberately narrow first release:

1. Versioned Production Formula with STANDARD/REPACKING and one main output.
2. Formula-based Production Orders with immutable snapshots and base-UOM requirement calculation.
3. Draft/release/in-progress/completed/closed/cancelled workflow.
4. Partial, multiple Material Issues with no over-issue and existing stock/batch protection.
5. Partial, multiple Finished Goods Receipts with one batch per receipt and no over-production.
6. Same-company/branch line warehouses, full Stock Movement/Balance/Batch Balance integration, order-level genealogy, and source links.
7. WIP/history using counts, percentages, and per-UOM values.
8. No reservation, manual Formula-less orders, by-products, stock-tracked waste, QC holds, reversal, mandatory costing, or Accounting dependency.

Implementation should not start until the defaults in Section 25—especially batch multiplicity, over-issue/production, expiry, warehouse multiplicity, and completion rules—are approved.

### Approved Phase-One refinements

- Waste supports two future modes: informational waste (quantity, UOM, reason and notes without stock movement) and stock-tracked waste represented by an Item received into a Waste, Reject, or By-product Warehouse. Formula outputs are schema-ready for `WASTE`, but Phase 1 exposes only MAIN output.
- Future configurable output flow may be Production → QC Warehouse → Finished Goods Warehouse. Formula destination defaults preserve this extension point; Phase 1 creates no QC transaction. Future Item configuration may include `shelf_life_days` and `expiry_calculation_basis`, with expiry calculated from manufacturing date plus shelf life.
- Clone Formula is approved: it creates a separately numbered draft, copies lines, suggests the next version, clears lifecycle audit, and never changes the source.
- Output-batch-to-input-batch genealogy is strategic. Formula provides structural identity only; exact genealogy is created later from immutable posted issue and receipt lines.
- Legacy `productions`, `production_inputs`, and `production_outputs` remain untouched and deprecated. New Formula tables are separate and legacy workflows are removed from normal navigation.

### Phase-One checkpoint

Production Formula/BOM Phase 1 is complete for the approved scope: versioned formulas, company and optional branch applicability, STANDARD/REPACKING types, effective periods, one MAIN output, proportional/fixed materials, default and line warehouse sources, requirement preview, draft/active/inactive lifecycle, cloning, and multi-company/branch access validation. Formula creation has no stock or inventory posting effect.

Non-blocking backlog: replace the native Source Warehouse selector with a reusable searchable warehouse autocomplete. Production Order, reservation, material issue, execution, QC, finished-goods receipt, stock posting, and batch genealogy are not implemented in this phase.
