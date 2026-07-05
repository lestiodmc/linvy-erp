# TODO

This checklist supports the architecture in [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md). Keep implementation aligned with the inventory-first, modular ERP direction.

## Master Data

- [x] Company Master
- [x] Branch Master
- [x] Warehouse Type Master
- [x] Warehouse Master
- [x] Item Category ERP-ready enhancement
- [x] Unit of Measure ERP-ready enhancement
- [ ] Brand Master
- [ ] Item Master ERP-ready enhancement
- [ ] Customer Master enhancement
- [ ] Supplier Master enhancement
- [ ] Currency Master
- [ ] Tax Master
- [ ] Employee Master
- [ ] User and Role refinement

## System Foundation

- [ ] Service Layer for business workflows
- [ ] Form Request validation for complex forms
- [ ] DocumentSequenceService support for daily numbering
- [ ] DocumentSequenceService support for monthly numbering
- [ ] DocumentSequenceService support for yearly numbering
- [ ] Configurable approval engine
- [ ] Module access management
- [ ] Reusable Blade components for forms, buttons, tables, and badges
- [ ] Audit trail strategy
- [ ] Soft delete strategy for master data where appropriate
- [ ] Repository layer evaluation for future scaling

## Purchase

- [ ] Purchase Request workflow
- [ ] Purchase Request approval levels
- [ ] Purchase Order workflow
- [ ] Purchase Order approval levels
- [ ] Receiving with warehouse per line
- [ ] Receiving posting service
- [ ] Purchase receive Stock Movement creation
- [ ] Purchase status synchronization

## Inventory

- [ ] Stock Movement service
- [ ] Stock Balance maintenance from Stock Movement
- [ ] Warehouse Transfer workflow
- [ ] Stock Adjustment workflow
- [ ] Stock Opname workflow
- [ ] Lot tracking foundation
- [ ] Serial number tracking foundation
- [ ] Location support design for future warehouse locations

## Sales

- [ ] Sales Order workflow
- [ ] Delivery Order workflow
- [ ] Sales delivery Stock Movement creation
- [ ] Invoice integration planning
- [ ] Customer credit and receivable planning

## Production

- [ ] Production Order workflow
- [ ] Material Issue workflow
- [ ] Production Process status flow
- [ ] Finished Goods output workflow
- [ ] Production input and output Stock Movement creation

## Accounting

- [ ] Optional accounting module toggle
- [ ] Journal Entry foundation
- [ ] General Ledger foundation
- [ ] Accounts Receivable planning
- [ ] Accounts Payable planning
- [ ] Cash Bank planning
- [ ] Fixed Asset planning
- [ ] Financial Statement planning
- [ ] Inventory event listeners for future accounting integration

## Future Modules

- [ ] CRM
- [ ] MRP
- [ ] Quality Control
- [ ] Maintenance
- [ ] Asset
- [ ] HR
- [ ] Payroll
- [ ] POS
- [ ] Mobile App
- [ ] Barcode
- [ ] RFID
- [ ] Dashboard BI
- [ ] API
