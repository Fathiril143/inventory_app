# Inventory Management System

Aplikasi pengelolaan persediaan barang menggunakan PHP native, PDO, dan MySQL.

## Fitur

- Login dengan password yang disimpan menggunakan `password_hash`.
- Role admin dan staff.
- Data kategori, produk, dan supplier.
- Relasi many-to-many antara produk dan supplier.
- Pencarian produk berdasarkan nama atau SKU.
- Filter kategori, pengurutan, dan pagination.
- Transaksi barang masuk dan barang keluar.
- Riwayat transaksi stok.
- Dashboard ringkasan stok dan laporan per kategori.

## Persyaratan

- XAMPP dengan Apache dan MySQL.
- PHP dengan ekstensi PDO MySQL.
- Browser web.

## Instalasi

1. Salin folder `inventory_app_1` ke `C:\xampp\htdocs`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
4. Buat database bernama `inventory_db_1`.
5. Pilih database tersebut, lalu impor file `database/schema.sql`.
6. Periksa `config/database.php`. Sesuaikan nama database, username, dan password MySQL.
7. Buka XAMPP Shell, lalu jalankan:
8. masuk sebagai admin 
    username = aril22
    nama admin = aril keren
    pass = arneslot25
9. masuk sebagai staff
    username = fathirchek
    nama = fathiril haq
    pass = jurgen19
10. buka aplikasi melalui `http://localhost/inventory_app_/public/login.php` 
 
   ```text
   cd C:\xampp\htdocs\inventory_app_1
   php seed-admin.php