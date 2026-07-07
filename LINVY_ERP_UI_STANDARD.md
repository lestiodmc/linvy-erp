# Linvy ERP UI Standard

## 1. General Principle
Linvy ERP must not look like simple CRUD pages.
Every transaction page must look like a professional ERP document.

Inspired by:
- Microsoft Dynamics 365 Business Central
- SAP Business One
- Odoo Enterprise

## 2. Transaction Page Structure
Every transaction page must use this structure:

1. Page Header
2. General Information
3. Document / Warehouse / Partner Information
4. Transaction Lines Grid
5. Summary Footer
6. Document Audit Information
7. Action Buttons

## 3. Page Header
Header must contain:
- Document title
- Short description
- Status badge on the right

Example:
Warehouse Transfer
Transfer inventory between warehouses.
[DRAFT]

## 4. General Information Layout
Use compact two-column layout on desktop.

Example:
Transfer No        Transfer Date
Company            Branch
Status             Created By

Do not use one long vertical form unless screen is mobile.

## 5. Card Rules
Cards must be compact.
Avoid large empty whitespace.
Use consistent padding.
Recommended:
- Card padding: 16px to 20px
- Section gap: 12px to 16px
- Field gap: 10px to 12px

## 6. Transaction Lines Grid
This is mandatory.

Transaction lines must use Excel-style horizontal table grid.

Each line MUST occupy exactly one horizontal row.

Never stack line fields vertically.

Correct:
| SKU | Item | Batch | Expiry | Available | Qty | Remaining | UOM | Remark | Action |

Incorrect:
SKU
[input]
Item
[input]
Batch
[input]

## 7. Grid Rules
- Header row must be visible.
- Each row should be compact.
- Recommended row height: 38px to 44px.
- Use horizontal scroll if columns do not fit.
- Inputs inside grid must be small and aligned.
- Numbers must be right-aligned.
- Text must be left-aligned.
- Action column must be narrow.

## 8. Editable Grid Behavior
Every editable transaction line should support:
- Searchable item dropdown
- Readonly auto-filled item name
- Batch dropdown if batch tracked
- No Batch readonly if not batch tracked
- Expiry readonly
- Available qty readonly
- Transaction qty editable
- Remaining qty realtime
- UOM readonly
- Remark optional
- Delete row action

## 9. Item Dropdown Standard
Item dropdown must show:
SKU
Item Name
Available Qty if relevant

Example:
RM001 - Kepiting Raw Jumbo
Available: 340 KG

Do not use free text for inventory item selection.

## 10. Batch Dropdown Standard
Batch dropdown must show:
Batch No
Available Qty
Expiry Date

Example:
A1
Available: 240 KG
Expiry: 31 Jul 2027

Do not allow manual batch typing for existing stock transactions.

## 11. Quantity Formatting
Display quantities with maximum 2 decimals in UI.
Do not display 0.000000 unless precision is specifically required.

Correct:
340.00 KG

Incorrect:
340.000000 KG

## 12. Status Badges
Use consistent badge colors:

Draft = gray
Posted = green
Approved = green
Cancelled = red
Pending = yellow
In Progress = blue

## 13. Action Buttons
Bottom-right action order:

Cancel / Back
Save Draft
Post / Submit / Approve

Primary action must be visually clear.

## 14. Summary Footer
Every transaction with lines must show:
- Total Lines
- Total Quantity
- Optional total amount if financial document

Example:
Total Lines: 3
Total Qty: 420 KG

## 15. Audit Information
Show audit information in compact section:
- Created By
- Created At
- Updated By
- Updated At
- Posted By
- Posted At
- Cancelled By
- Cancelled At

Hide empty values.

## 16. Filters Standard
List/index pages should use compact filters:
- Keyword
- Date From
- Date To
- Company
- Branch
- Status
- Relevant document filters

Default:
Date From = first day of current month
Date To = today

## 17. Warehouse Filter Rules
If Branch is selected, warehouse dropdown must only show warehouses from that branch.
Do not show warehouses from other branches.

## 18. Inventory Transaction Rules
Inventory transaction pages must clearly show:
- Source warehouse if stock leaves
- Destination warehouse if stock enters
- Item
- Batch
- Expiry
- Available qty
- Transaction qty
- UOM
- Reference document

## 19. Responsive Rule
Desktop first.
Mobile can stack sections, but transaction line grid may use horizontal scroll.

## 20. Main Warning for Codex
Do not convert transaction lines into vertical cards.
Do not create CRUD-like forms for transaction documents.
Always use ERP-style document layout and Excel-style transaction grid.
