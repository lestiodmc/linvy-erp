# Linvy ERP - Project Architecture

Version : 1.0
Framework : Laravel 12
Database : MySQL
UI : Blade + TailwindCSS
Architecture : MVC + Service Layer
Goal : Enterprise Distribution ERP

---

# Project Goal

Linvy ERP adalah ERP modern yang difokuskan untuk perusahaan Distributor, Trading, Manufacturing ringan, dan Food Processing.

Target utama:

- Enterprise Ready
- Multi Company
- Multi Branch
- Multi Warehouse
- Modular
- Inventory First
- Accounting Optional Module

---

# Core Philosophy

Inventory adalah pusat seluruh transaksi.

Semua transaksi yang mempengaruhi stok harus menghasilkan Stock Movement.

Stock Balance bukan disimpan manual tetapi merupakan hasil dari Stock Movement.

Accounting mengambil data dari transaksi Inventory.

Bukan sebaliknya.

---

# Module Architecture

Master Data

- Company
- Branch
- Warehouse
- Warehouse Type
- Unit Of Measure
- Item Category
- Item
- Customer
- Supplier
- Currency
- Tax
- Employee
- User
- Role

Purchasing

Purchase Request

↓

Approval

↓

Purchase Order

↓

Receiving

↓

Stock Movement

↓

Stock Balance

Production

Production Order

↓

Material Issue

↓

Production Process

↓

Finished Goods

↓

Stock Movement

Sales

Sales Order

↓

Delivery Order

↓

Stock Movement

↓

Invoice (Optional)

Inventory

Warehouse Transfer

Stock Adjustment

Stock Opname

Stock Movement

Stock Balance

Accounting (Optional Module)

Journal

General Ledger

AR

AP

Cash Bank

Fixed Asset

Financial Statement

Accounting module can be disabled.

Inventory must continue working.

---

# Inventory Architecture

Company

↓

Branch

↓

Warehouse

↓

(Location - Future)

Warehouse belongs to Branch.

Branch belongs to Company.

---

# Warehouse Type

Warehouse Type defines warehouse purpose.

Examples

- Raw Material
- Packaging
- Production
- Finished Goods
- QC
- Transit
- Reject
- Consumable

Warehouse references Warehouse Type.

---

# Item Architecture

Item DOES NOT belong permanently to Warehouse.

Item stores:

Default Warehouse Type

Example

RM001

Default Warehouse Type

Raw Material

FG001

Default Warehouse Type

Finished Goods

Receiving automatically suggests warehouse.

User may override warehouse.

---

# Receiving Architecture

Warehouse is NOT stored in Header.

Warehouse exists per Receiving Line.

Receiving Line

Item

Warehouse

Qty

Cost

Notes

Each line may go to different warehouse.

---

# Stock Movement

Every stock transaction creates Stock Movement.

Movement Types

PURCHASE_RECEIVE

SALE_DELIVERY

TRANSFER_OUT

TRANSFER_IN

PRODUCTION_INPUT

PRODUCTION_OUTPUT

ADJUSTMENT_PLUS

ADJUSTMENT_MINUS

REJECT

---

# Stock Balance

Stock Balance is maintained automatically.

Grouped by

Company

Branch

Warehouse

Item

Future

Location

Lot

Serial Number

---

# Document Number

All documents use Document Sequence Service.

Examples

PR/2026/07/0001

PO/2026/07/0001

RCV/2026/07/0001

SO/2026/07/0001

DO/2026/07/0001

Number generation must support:

- Daily
- Monthly
- Yearly

Monthly is default.

Must support concurrent users.

---

# Approval Engine

Approval Level is configurable.

Purchase Request

1 Level

2 Level

3 Level

Purchase Order

1 Level

2 Level

Approval must not be hardcoded.

---

# Status Flow

Purchase Request

Draft

↓

Submitted

↓

Approved

↓

Closed

Rejected

Cancelled

Purchase Order

Draft

↓

Submitted

↓

Approved

↓

Partially Received

↓

Fully Received

↓

Closed

Rejected

Cancelled

Receiving

Draft

↓

Posted

↓

Cancelled

Production

Draft

↓

Released

↓

In Process

↓

Finished

↓

Closed

Sales Order

Draft

↓

Approved

↓

Delivered

↓

Closed

---

# UI Philosophy

Modern

Minimal

Enterprise

Responsive

Fast

Consistent

No duplicated components.

Reusable Blade Components.

---

# Coding Standard

Business Logic

↓

Service Layer

Controller

↓

Validation

↓

Service

↓

Repository (future)

↓

Model

Do not put business logic inside Blade.

---

# Future Modules

CRM

MRP

Quality Control

Maintenance

Asset

HR

Payroll

POS

Mobile App

Barcode

RFID

Dashboard BI

API

---

# Long Term Goal

Linvy ERP should be scalable from:

1 Company

↓

100 Companies

1 Warehouse

↓

1000 Warehouses

Without redesigning the database.
