# Sistem Inventaris Barang

Aplikasi manajemen inventaris barang (CRUD) yang dibangun menggunakan PHP 8.2 native, MySQL, dan Tailwind CSS. Tidak menggunakan framework maupun Composer — murni PHP dengan praktik terbaik modern.

> **Mata Kuliah:** Pemrograman Web Lanjut  
> **Kelas:** A12  
> **Tahun:** 2026

## Fitur Aplikasi

### Autentikasi & Manajemen Pengguna

- **Registrasi akun** dengan validasi username dan email unik
- **Login** dengan session management + remember-me cookie (30 hari)
- **Token rotasi otomatis** untuk keamanan remember-me
- **Logout** dengan pembersihan session dan cookie
- **Proteksi rute** — hanya pengguna terautentikasi yang dapat mengakses inventory

### Manajemen Inventaris

- **Tambah, Lihat, Ubah, Hapus** data barang (CRUD) dengan ownership validation
- **Pencarian langsung** dengan debounce 300ms (tetap berfungsi tanpa JavaScript)
- **Pagination** pada halaman daftar barang (10 item per halaman)
- **Peringatan stok rendah** dengan ambang batas yang dapat dikonfigurasi
- **Data per-user** — setiap pengguna hanya melihat barang miliknya

### Keamanan & Validasi

- **Perlindungan CSRF** pada seluruh formulir
- **Validasi sisi server** dengan pesan kesalahan di formulir
- **Prepared statements** untuk mencegah SQL injection
- **Password hashing** dengan Bcrypt
- **Session security** dengan konfigurasi khusus

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
├── index.php                    # Front controller & router utama
├── config/
│   └── database.php             # Konfigurasi koneksi database
├── sql/
│   └── schema.sql               # Skema database + data awal (seed)
├── src/
│   ├── Core/
│   │   ├── Autoloader.php       # Autoloader tanpa Composer
│   │   ├── Database.php         # Singleton koneksi PDO
│   │   └── Auth.php             # User authentication & session management
│   ├── Models/
│   │   ├── Item.php             # Value object / DTO data barang
│   │   └── User.php             # Value object / DTO data pengguna
│   ├── Repositories/
│   │   ├── ItemRepository.php   # Query SQL untuk barang
│   │   └── UserRepository.php   # Query SQL untuk pengguna & remember tokens
│   └── Controllers/
│       ├── ItemController.php   # Aksi CRUD barang
│       ├── SearchController.php # Pencarian + pagination barang
│       ├── AlertController.php  # Peringatan stok rendah
│       └── AuthController.php   # Login, registrasi, logout
└── views/
    ├── layout.php               # Fungsi pembantu (csrf, old)
    ├── layout/
    │   ├── header.php           # Pembuka HTML + navigasi + user menu
    │   └── footer.php           # Penutup HTML
    ├── 404.php
    ├── auth/
    │   ├── login.php            # Form login
    │   └── register.php         # Form registrasi
    ├── items/
    │   ├── index.php            # Daftar barang + pencarian
    │   ├── create.php           # Formulir tambah barang
    │   └── edit.php             # Formulir ubah barang
    └── partials/
        ├── table.php            # Tabel barang + pagination
        └── alert_banner.php     # Banner peringatan stok rendah
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

### 6. Login dengan Akun Default

Setelah membuka aplikasi, Anda akan diarahkan ke halaman login. Gunakan kredensial berikut:

```
Username: admin
Password: Admin@12345
```

> **Catatan:** Data pengguna dan 5 barang contoh sudah diimpor otomatis melalui file `sql/schema.sql`

## Daftar Rute Aplikasi

### Autentikasi (Publik)

| Metode | URI              | Keterangan                     |
|--------|------------------|--------------------------------|
| GET    | `/auth/login`    | Tampilkan form login           |
| POST   | `/auth/auth`     | Proses login (session + cookie)|
| GET    | `/auth/register` | Tampilkan form registrasi      |
| POST   | `/auth/store`    | Simpan akun baru              |
| POST   | `/auth/logout`   | Logout & hapus session/cookie  |

### Inventaris (Terproteksi - Autentikasi Diperlukan)

| Metode | URI                 | Keterangan                    |
|--------|---------------------|-------------------------------|
| GET    | `/`                 | Daftar barang + pencarian     |
| GET    | `/items/create`     | Tampilkan formulir tambah     |
| POST   | `/items/store`      | Simpan barang baru            |
| GET    | `/items/edit?id=`   | Tampilkan formulir ubah       |
| POST   | `/items/update`     | Perbarui data barang          |
| POST   | `/items/delete`     | Hapus barang                  |
| GET    | `/alerts/low-stock` | Data stok rendah per user     |

## Struktur Data

### Tabel Users

| Kolom            | Tipe      | Keterangan                    |
|------------------|-----------|-------------------------------|
| `id`             | INT       | Primary key auto-increment    |
| `username`       | VARCHAR   | Nama pengguna (unik)          |
| `email`          | VARCHAR   | Email (unik)                  |
| `password_hash`  | VARCHAR   | Hash password (Bcrypt)        |
| `created_at`     | TIMESTAMP | Waktu pembuatan akun          |
| `updated_at`     | TIMESTAMP | Waktu pembaruan terakhir      |

### Tabel Items

| Kolom        | Tipe      | Keterangan                 |
|--------------|-----------|----------------------------|
| `id`         | INT       | Primary key auto-increment |
| `user_id`    | INT       | Foreign key ke users       |
| `item_name`  | VARCHAR   | Nama barang                |
| `quantity`   | INT       | Jumlah stok                |
| `price`      | DECIMAL   | Harga dalam Rupiah (IDR)   |
| `entry_date` | DATE      | Tanggal barang masuk       |
| `created_at` | TIMESTAMP | Waktu pembuatan data       |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir   |

### Tabel Remember Tokens

| Kolom       | Tipe      | Keterangan                |
|-------------|-----------|---------------------------|
| `id`        | INT       | Primary key auto-increment |
| `user_id`   | INT       | Foreign key ke users       |
| `token_hash` | VARCHAR   | Hash token remember-me    |
| `expires_at` | TIMESTAMP | Waktu kadaluarsa token    |
| `created_at` | TIMESTAMP | Waktu pembuatan token     |

## Konfigurasi

### Ambang Batas Stok Rendah

Ambang batas stok rendah secara default adalah **5 unit**. Barang dengan jumlah stok ≤ 5 akan menampilkan lencana peringatan dan muncul pada banner peringatan di halaman utama.

Untuk mengubah nilai ambang batas, perbarui konstanta berikut pada file [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php):

```php
private const int LOW_STOCK_THRESHOLD = 5;
```

### Session & Remember-Me

- **Session ID:** Diatur otomatis berdasarkan konfigurasi PHP
- **Remember-Me Cookie:** Berlaku selama **30 hari** sejak login
- **Token Rotation:** Token di-rotate setiap kali remember-me digunakan untuk meningkatkan keamanan
- **Cookie Options:** Aman (secure flag), HttpOnly, SameSite=Lax

Untuk mengubah durasi remember-me, edit konstanta di [src/Core/Auth.php](src/Core/Auth.php):

```php
private const COOKIE_EXPIRY = 86400 * 30; // 30 hari dalam detik
```

## Proses Aplikasi

Untuk penjelasan detail tentang alur bisnis, alur pengguna, dan flow sistem, lihat dokumentasi: [PROCESS_DOCUMENT.md](PROCESS_DOCUMENT.md)
