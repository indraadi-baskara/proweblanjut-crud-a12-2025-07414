# Sistem Inventaris Barang

Aplikasi manajemen inventaris barang (CRUD) yang dibangun menggunakan PHP 8.2 native, MySQL, dan Tailwind CSS. Tidak menggunakan framework maupun Composer — murni PHP dengan praktik terbaik modern.

> **Mata Kuliah:** Pemrograman Web Lanjut  
> **Kelas:** A12  
> **Tahun:** 2026

## Fitur Aplikasi

- **Tambah, Lihat, Ubah, Hapus** data barang (CRUD)
- **Pencarian langsung** dengan debounce 300ms (tetap berfungsi tanpa JavaScript)
- **Pagination** pada halaman daftar barang
- **Peringatan stok rendah** dengan ambang batas yang dapat dikonfigurasi
- **Perlindungan CSRF** pada seluruh formulir
- **Validasi sisi server** dengan pesan kesalahan secara langsung di formulir

## Teknologi yang Digunakan

- **PHP** 8.2.12 (native, tanpa framework)
- **MySQL / MariaDB** melalui PDO
- **Tailwind CSS** v3 via Play CDN (tanpa proses build)
- **Apache** melalui XAMPP

## Persyaratan Sistem

- [XAMPP](https://www.apachefriends.org/) dengan komponen:
  - PHP 8.2.12
  - Apache 2.4
  - MySQL / MariaDB
- Modul `mod_rewrite` diaktifkan pada Apache

## Struktur Proyek

```
proweblanjut-crud-a12-2025-07414/
├── .htaccess
├── index.php                   # Front controller & router utama
├── config/
│   └── database.php            # Konfigurasi koneksi database
├── sql/
│   └── schema.sql              # Skema database + data awal (seed)
├── src/
│   ├── Core/
│   │   ├── Autoloader.php      # Autoloader tanpa Composer
│   │   └── Database.php        # Singleton koneksi PDO
│   ├── Models/
│   │   └── Item.php            # Value object / DTO data barang
│   ├── Repositories/
│   │   └── ItemRepository.php  # Seluruh query SQL
│   └── Controllers/
│       ├── ItemController.php  # Aksi CRUD barang
│       ├── SearchController.php# Pencarian + pagination
│       └── AlertController.php # Peringatan stok rendah
└── views/
    ├── layout.php              # Fungsi pembantu (csrf, old)
    ├── layout/
    │   ├── header.php          # Pembuka HTML + navigasi
    │   └── footer.php          # Penutup HTML
    ├── 404.php
    ├── items/
    │   ├── index.php           # Daftar barang + pencarian
    │   ├── create.php          # Formulir tambah barang
    │   └── edit.php            # Formulir ubah barang
    └── partials/
        ├── table.php           # Tabel + pagination (target Ajax)
        └── alert_banner.php    # Banner peringatan stok rendah
```

## Cara Instalasi dan Menjalankan Aplikasi

### 1. Clone repositori

```bash
git clone https://github.com/username/proweblanjut-crud-a12-2025-07414.git
```

Letakkan folder proyek di dalam direktori `htdocs` milik XAMPP:

```
C:\xampp\htdocs\proweblanjut-crud-a12-2025-07414\
```

### 2. Aktifkan `mod_rewrite` pada Apache

Buka file `C:\xampp\apache\conf\httpd.conf`, kemudian pastikan baris berikut **tidak dikomentari** (tidak ada tanda `#` di depannya):

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Selanjutnya, cari blok `<Directory "C:/xampp/htdocs">` dan pastikan nilainya sebagai berikut:

```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
    ...
</Directory>
```

Setelah melakukan perubahan, restart Apache melalui XAMPP Control Panel.

### 3. Buat database

Buka **phpMyAdmin** di `http://localhost/phpmyadmin`, kemudian lakukan salah satu cara berikut:

- Impor file `sql/schema.sql` melalui tab **Import**, atau
- Jalankan isi file tersebut melalui konsol SQL

Proses ini akan membuat database, tabel, serta menyisipkan 5 data barang awal.

### 4. Konfigurasi koneksi database

Buka file `config/database.php` dan sesuaikan kredensial jika diperlukan:

```php
return [
    'host'    => 'localhost',
    'port'    => 3306,
    'dbname'  => 'proweblanjut_crud_a12_2025_07414',
    'charset' => 'utf8mb4',
    'user'    => 'root',
    'pass'    => '',   // XAMPP default tidak menggunakan kata sandi
];
```

### 5. Buka di browser

```
http://localhost/proweblanjut-crud-a12-2025-07414/
```

## Daftar Rute Aplikasi

| Metode | URI                  | Keterangan                  |
|--------|----------------------|-----------------------------|
| GET    | `/`                  | Daftar barang + pencarian   |
| GET    | `/items/create`      | Tampilkan formulir tambah   |
| POST   | `/items/store`       | Simpan barang baru          |
| GET    | `/items/edit?id=`    | Tampilkan formulir ubah     |
| POST   | `/items/update`      | Perbarui data barang        |
| POST   | `/items/delete`      | Hapus barang                |
| GET    | `/alerts/low-stock`  | Data stok rendah            |

## Struktur Data Barang

| Kolom        | Tipe      | Keterangan                      |
|--------------|-----------|---------------------------------|
| `id`         | INT       | Primary key auto-increment      |
| `item_name`  | VARCHAR   | Nama barang                     |
| `quantity`   | INT       | Jumlah stok                     |
| `price`      | DECIMAL   | Harga dalam Rupiah (IDR)        |
| `entry_date` | DATE      | Tanggal barang masuk            |

## Konfigurasi Ambang Batas Stok Rendah

Ambang batas stok rendah secara default adalah **5 unit**. Barang dengan jumlah stok ≤ 5 akan menampilkan lencana peringatan dan muncul pada banner peringatan di halaman utama.

Untuk mengubah nilai ambang batas, perbarui konstanta berikut pada file `src/Repositories/ItemRepository.php`:

```php
private const int LOW_STOCK_THRESHOLD = 5;
```
