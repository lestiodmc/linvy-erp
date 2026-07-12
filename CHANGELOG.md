# Changelog

All notable progress for Linvy ERP is tracked here.

This project follows the architecture and long-term direction defined in [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md).

## Unreleased

### Added

- Company Master foundation for multi-company ERP structure.
- Branch Master foundation connected to company hierarchy.
- Warehouse Type Master for defining warehouse purposes such as Raw Material, Packaging, Production, Finished Goods, QC, Transit, Reject, and Consumable.
- Warehouse Master aligned with the rule that warehouse belongs to branch and branch belongs to company.
- Item Category master enhanced toward ERP-ready behavior.
- Unit of Measure master enhanced toward ERP-ready behavior.
- Search and filter behavior for shared master resource lists.
- Seeder improvements with more realistic demo/master data.

### In Progress

### Production Formula Phase 1 checkpoint

- Completed versioned Production Formula/BOM with STANDARD and REPACKING types, one main output, proportional/fixed material lines, effective periods, default and line warehouse sources, requirement preview, and draft/active/inactive lifecycle.
- Added company/optional branch applicability, branch-scoped access validation, automatic numbering/versioning, cloning, and item/UOM eligibility validation.
- Formula stage creates no inventory posting or stock movement.
- Non-blocking backlog: replace native Source Warehouse selects with reusable searchable warehouse autocomplete.

- Brand Master for item classification and future master data consistency.
- Item Master enhancement for ERP-ready inventory, purchasing, sales, warehouse type defaults, and future accounting integration.

### Fixed

- Shared master resource list search/filter flow.
- Slow master resource index loading caused by unnecessary option-list queries on index pages.
- Blade view cache cleanup after shared resource view updates.

### Notes

- Inventory remains the core module.
- Stock-affecting transactions must continue to create Stock Movement records.
- Stock Balance must be maintained from Stock Movement, not manually edited.
- Accounting remains optional and must consume inventory transaction data instead of controlling inventory.
