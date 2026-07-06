# INVENTORY_ARCHITECTURE.md

# Linvy ERP — Inventory Architecture

## 1. Tujuan Modul Inventory

Modul Inventory digunakan untuk mengatur alur stok barang di Linvy ERP, mulai dari penerimaan barang, penyimpanan di warehouse, perpindahan antar warehouse, penyesuaian stok, hingga histori pergerakan stok.

Modul ini menjadi fondasi untuk modul berikutnya:

- Purchase
- Sales
- Service
- Stock Opname
- Inventory Valuation
- Accounting / Jurnal Otomatis

---

## 2. Prinsip Utama Inventory

Inventory tidak boleh hanya menyimpan angka stok akhir.

Setiap perubahan stok wajib berasal dari transaksi yang jelas dan tercatat pada stock movement.

Contoh sumber perubahan stok:

- Receive Purchase Order
- Stock Adjustment
- Stock Transfer
- Sales Delivery
- Service Usage
- Return
- Stock Opname

---

## 3. Struktur Lokasi Stok

### 3.1 Branch

Branch adalah cabang atau lokasi bisnis utama.

Contoh:

- Head Office
- Cabang Mojokerto
- Cabang Surabaya

Branch digunakan untuk memisahkan stok, transaksi, dan laporan antar cabang.

---

### 3.2 Warehouse Type

Warehouse Type adalah jenis gudang.

Contoh:

- Raw Material Warehouse
- Finished Goods Warehouse
- Sparepart Warehouse
- Service Warehouse
- Return Warehouse
- Damaged Warehouse

Warehouse Type membantu menentukan default warehouse berdasarkan jenis item.

---

### 3.3 Warehouse

Warehouse adalah gudang aktual yang berada di bawah branch.

Contoh:

| Branch    | Warehouse Type | Warehouse                            |
| --------- | -------------- | ------------------------------------ |
| Mojokerto | Raw Material   | Mojokerto - Raw Material Warehouse   |
| Mojokerto | Finished Goods | Mojokerto - Finished Goods Warehouse |
| Surabaya  | Raw Material   | Surabaya - Raw Material Warehouse    |

Relasi:

```text
Branch
  └── Warehouse
        └── Warehouse Type
```

---

## 4. Item Master dan Default Warehouse

Setiap item dapat memiliki default warehouse type.

Contoh:

| Item            | Item Type | Default Warehouse Type   |
| --------------- | --------- | ------------------------ |
| Raw Material A  | Inventory | Raw Material Warehouse   |
| Finished Good A | Inventory | Finished Goods Warehouse |
| Oli Mesin       | Inventory | Service Warehouse        |
| Jasa Service    | Service   | Tidak menggunakan stok   |

Saat transaksi receive dibuat, sistem dapat otomatis menentukan warehouse berdasarkan:

```text
Branch di header transaksi
+ Default Warehouse Type dari item
= Warehouse tujuan
```

Contoh:

```text
Branch: Mojokerto
Item: Raw Material A
Default Warehouse Type: Raw Material Warehouse

Maka warehouse otomatis:
Mojokerto - Raw Material Warehouse
```

---

## 5. Receive Purchase Order

Pada Receive Purchase Order, header wajib memiliki branch.

Warehouse pada detail dapat otomatis terisi berdasarkan branch dan default warehouse type item.

Namun user tetap boleh mengganti warehouse jika memiliki akses.

### Alur Receive

```text
Purchase Order
   ↓
Receive Header
   - Receive Number
   - Receive Date
   - Supplier
   - Branch
   ↓
Receive Detail
   - Item
   - Qty Receive
   - UOM
   - Warehouse
   ↓
Submit / Post
   ↓
Stock Movement terbentuk
```

---

## 6. Hak Akses Branch dan Warehouse

User dapat diberikan akses berdasarkan branch.

Contoh:

| User            | Branch Access  |
| --------------- | -------------- |
| Admin           | Semua branch   |
| Staff Mojokerto | Mojokerto only |
| Staff Surabaya  | Surabaya only  |

Efeknya:

- User hanya melihat transaksi branch yang diizinkan
- User hanya bisa memilih warehouse dari branch yang diizinkan
- User tidak boleh melakukan receive ke warehouse branch lain tanpa akses

---

## 7. Stock Movement

Setiap perubahan stok wajib mencatat movement.

Field utama stock movement:

| Field              | Keterangan                         |
| ------------------ | ---------------------------------- |
| Transaction Type   | RCV, ADJ, TRF, DO, SERVICE, RETURN |
| Transaction Number | Nomor dokumen sumber               |
| Transaction Date   | Tanggal transaksi                  |
| Branch             | Branch transaksi                   |
| Warehouse          | Warehouse stok                     |
| Item               | Barang                             |
| Qty In             | Jumlah masuk                       |
| Qty Out            | Jumlah keluar                      |
| UOM                | Satuan                             |
| Reference          | Referensi dokumen                  |
| Created By         | User pembuat                       |

Contoh:

```text
Receive PO:
Qty In = 10
Qty Out = 0

Sales Delivery:
Qty In = 0
Qty Out = 5
```

---

## 8. Stock Balance

Stock balance adalah hasil ringkasan dari stock movement.

Stock balance disimpan per:

```text
Branch
+ Warehouse
+ Item
+ UOM / Base UOM
```

Contoh:

| Branch    | Warehouse                | Item           | Qty |
| --------- | ------------------------ | -------------- | --- |
| Mojokerto | Raw Material Warehouse   | Raw Material A | 100 |
| Mojokerto | Finished Goods Warehouse | Product A      | 50  |

Stock balance tidak boleh diinput manual tanpa transaksi.

Jika ada perubahan manual, harus melalui Stock Adjustment.

---

## 9. Stock Adjustment

Stock Adjustment digunakan untuk menambah atau mengurangi stok karena koreksi.

Contoh penggunaan:

- Selisih fisik
- Barang rusak
- Salah input
- Koreksi awal stok
- Penyesuaian stock opname

Alur:

```text
Create Adjustment
   ↓
Pilih Branch
   ↓
Pilih Warehouse
   ↓
Input Item dan Qty Adjustment
   ↓
Submit / Post
   ↓
Stock Movement terbentuk
```

---

## 10. Stock Transfer

Stock Transfer digunakan untuk memindahkan barang antar warehouse.

Contoh:

```text
Dari:
Mojokerto - Raw Material Warehouse

Ke:
Mojokerto - Service Warehouse
```

Atau antar branch jika diizinkan:

```text
Dari:
Mojokerto - Finished Goods Warehouse

Ke:
Surabaya - Finished Goods Warehouse
```

Alur:

```text
Create Transfer
   ↓
Branch From
Warehouse From
   ↓
Branch To
Warehouse To
   ↓
Item + Qty
   ↓
Submit
   ↓
Stock Out dari warehouse asal
Stock In ke warehouse tujuan
```

---

## 11. Status Dokumen Inventory

Standar status dokumen inventory:

| Status    | Keterangan                    |
| --------- | ----------------------------- |
| Draft     | Masih bisa diedit             |
| Submitted | Sudah diajukan                |
| Posted    | Sudah memengaruhi stok        |
| Cancelled | Dibatalkan                    |
| Closed    | Selesai dan tidak bisa diedit |

Catatan:

- Draft belum memengaruhi stok
- Posted sudah memengaruhi stok
- Cancelled tidak boleh memengaruhi stok
- Jika dokumen posted dibatalkan, harus membuat reversal movement

---

## 12. Inventory Transaction Types

Kode transaksi inventory:

| Code       | Name             | Effect    |
| ---------- | ---------------- | --------- |
| RCV        | Receive Purchase | Stock In  |
| ADJ-IN     | Adjustment In    | Stock In  |
| ADJ-OUT    | Adjustment Out   | Stock Out |
| TRF-OUT    | Transfer Out     | Stock Out |
| TRF-IN     | Transfer In      | Stock In  |
| DO         | Delivery Order   | Stock Out |
| SERVICE    | Service Usage    | Stock Out |
| RETURN-IN  | Return In        | Stock In  |
| RETURN-OUT | Return Out       | Stock Out |

---

## 13. Validasi Penting

Sistem wajib melakukan validasi berikut:

1. Branch wajib diisi pada transaksi inventory.
2. Warehouse wajib sesuai dengan branch.
3. Item inventory wajib memiliki warehouse tujuan.
4. Qty tidak boleh nol atau negatif.
5. Stock tidak boleh minus jika item tidak mengizinkan negative stock.
6. User hanya boleh memilih branch sesuai akses.
7. Dokumen posted tidak boleh diedit langsung.
8. Perubahan dokumen posted harus melalui reversal atau adjustment.

---

## 14. Inventory Reports

Laporan inventory minimum:

### 14.1 Stock Balance Report

Menampilkan stok akhir per branch, warehouse, dan item.

Filter:

- Date
- Branch
- Warehouse
- Item
- Item Category

---

### 14.2 Stock Card Report

Menampilkan histori pergerakan stok per item.

Kolom:

- Date
- Transaction Type
- Transaction Number
- Warehouse
- Qty In
- Qty Out
- Balance

---

### 14.3 Stock Movement Report

Menampilkan semua movement stok.

Filter:

- Date From
- Date To
- Branch
- Warehouse
- Transaction Type
- Item

---

### 14.4 Low Stock Report

Menampilkan item yang stoknya di bawah minimum stock.

---

## 15. Hubungan Dengan Modul Purchase

Purchase menghasilkan stok saat Receive diposting.

```text
Purchase Request
   ↓
Purchase Order
   ↓
Receive
   ↓
Stock Movement
   ↓
Stock Balance
```

Purchase Order belum menambah stok.

Receive yang sudah posted menambah stok.

---

## 16. Hubungan Dengan Modul Sales

Sales akan mengurangi stok saat Delivery Order diposting.

```text
Sales Order
   ↓
Delivery Order
   ↓
Stock Movement
   ↓
Stock Balance
```

Sales Order belum mengurangi stok.

Delivery Order posted mengurangi stok.

---

## 17. Hubungan Dengan Modul Service

Transaksi service dapat menggunakan item inventory.

Contoh:

- Oli
- Sparepart
- Consumable
- Material service

Saat service selesai atau posted, item inventory akan mengurangi stok.

```text
Service Transaction
   ↓
Service Item Usage
   ↓
Stock Movement
   ↓
Stock Balance
```

Item bertipe service tidak mengurangi stok.

Item bertipe inventory mengurangi stok.

---

## 18. Rekomendasi Urutan Development Fase 2

Urutan development inventory:

1. Rapikan master warehouse
2. Tambahkan branch access pada inventory
3. Buat stock movement table
4. Buat stock balance table
5. Integrasikan Receive Purchase ke stock movement
6. Buat Stock Adjustment
7. Buat Stock Transfer
8. Buat Stock Card Report
9. Buat Stock Balance Report
10. Siapkan fondasi untuk Sales Delivery dan Service Usage

---

## 19. Catatan Desain UI

Standar UI mengikuti modul Purchase yang sudah dirapikan.

Filter default:

```text
Date From = awal bulan berjalan
Date To = hari ini
```

Filter umum:

- Date From
- Date To
- Branch
- Warehouse
- Status
- Item
- Search keyword

Tampilan filter harus compact agar tidak terlalu memakan tempat.

---

## 20. Kesimpulan

Inventory di Linvy ERP harus dibangun berbasis transaksi, bukan hanya saldo stok.

Fondasi utama inventory adalah:

```text
Branch
Warehouse
Item
Stock Movement
Stock Balance
```

Setelah struktur ini stabil, modul lain seperti Sales, Service, Stock Opname, dan Accounting akan lebih mudah dikembangkan.
