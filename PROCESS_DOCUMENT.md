# Proses Dokumen Aplikasi Inventaris Barang

Dokumen ini menjelaskan alur bisnis, proses sistem, dan arsitektur aplikasi **Sistem Inventaris Barang**.

## Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Alur Autentikasi](#alur-autentikasi)
3. [Alur Manajemen Inventaris](#alur-manajemen-inventaris)
4. [Arsitektur Sistem](#arsitektur-sistem)
5. [Flow Data](#flow-data)
6. [Mekanisme Keamanan](#mekanisme-keamanan)
7. [Penanganan Error](#penanganan-error)

---

## Gambaran Umum

Aplikasi ini adalah sistem manajemen inventaris berbasis web dengan fitur:

- **Multi-user support** — setiap pengguna memiliki inventaris terpisah
- **Session-based authentication** — dengan opsi remember-me hingga 30 hari
- **CRUD operations** — tambah, lihat, ubah, hapus barang
- **Real-time search** — pencarian barang dengan debounce 300ms
- **Low-stock alerts** — peringatan otomatis untuk barang dengan stok rendah

**Target User:** Staf warehouse / inventory manager

**Tech Stack:**
- Backend: PHP 8.2 native (no framework)
- Database: MySQL / MariaDB via PDO
- Frontend: HTML + Tailwind CSS + Vanilla JavaScript

---

## Alur Autentikasi

### 1. Halaman Login Publik

```
User membuka aplikasi
    ↓
Auth::initialize() → Cek session/remember-me
    ↓
Tidak ada session/token → Redirect ke /auth/login
    ↓
User lihat form login dengan field:
  - Username
  - Password
  - "Ingat saya selama 30 hari" (checkbox)
```

**File terkait:**
- [views/auth/login.php](views/auth/login.php)
- [src/Controllers/AuthController.php](src/Controllers/AuthController.php) → `login()`

### 2. Proses Login

```
User submit form POST /auth/auth
    ↓
AuthController::auth()
    ├─ Validasi CSRF token
    ├─ Validasi input (username, password tidak kosong)
    ├─ UserRepository::findByUsername($username)
    ├─ User::verifyPassword($password, $hash)
    │   └─ if valid: Auth::authenticate()
    │   └─ if invalid: set error, re-render form
    ↓
Auth::authenticate($username, $password, $rememberMe)
    ├─ Set $_SESSION["auth_user_id"]
    ├─ if $rememberMe == true:
    │   ├─ Generate random token (bin2hex(random_bytes(32)))
    │   ├─ Hash token dengan password_hash()
    │   ├─ UserRepository::createRememberToken($userId, $tokenHash, $expiryTime)
    │   ├─ Set HTTP cookie "remember_token" (30 hari, HttpOnly, Secure)
    │
    ↓
Redirect ke / (inventory list)
```

**File terkait:**
- [src/Core/Auth.php](src/Core/Auth.php) → `authenticate()`, `setRememberCookie()`
- [src/Repositories/UserRepository.php](src/Repositories/UserRepository.php) → `createRememberToken()`, `verifyRememberToken()`

### 3. Persistent Login (Remember-Me)

Jika session expired tapi remember-me cookie masih valid:

```
User kembali ke aplikasi
    ↓
Auth::initialize()
    ├─ Cek $_SESSION["auth_user_id"] → tidak ada
    ├─ Cek $_COOKIE["remember_token"] → ada
    ├─ UserRepository::verifyRememberToken($cookie)
    │   ├─ Query remember_tokens WHERE token_hash = hash($cookie)
    │   ├─ Cek apakah expires_at > now()
    │   ├─ Jika valid:
    │   │   ├─ Dapatkan user dari database
    │   │   ├─ Delete token lama
    │   │   ├─ Generate token baru (token rotation)
    │   │   ├─ Return { user, newToken }
    │   │
    │   └─ Jika invalid: clearRememberCookie()
    │
    ├─ Set $_SESSION["auth_user_id"] = $user->id
    ├─ Set cookie baru dengan token yang di-rotate
    │
    ↓
User tetap logged in
```

**Keamanan:**
- Token di-hash sebelum disimpan di database
- Token di-rotate setiap kali digunakan (mencegah token reuse)
- Token memiliki expiry time (30 hari)
- Cookie: HttpOnly (tidak bisa diakses JavaScript), Secure (HTTPS only)

### 4. Logout

```
User klik "Logout" → POST /auth/logout
    ↓
AuthController::logout()
    ├─ Validasi CSRF token
    ├─ Auth::logout()
    │   ├─ Unset $_SESSION["auth_user_id"]
    │   ├─ Unset $_SESSION["csrf_token"]
    │   ├─ clearRememberCookie()
    │   ├─ Jika ada remember token di database → hapus
    │   └─ Reset static $currentUser = null
    │
    ↓
Redirect ke /auth/login
```

---

## Alur Manajemen Inventaris

### Prerequisite

Semua rute inventory memerlukan autentikasi:

```php
Auth::requireAuth(); // Redirect ke login jika tidak authenticated
$user = Auth::currentUser(); // Dapatkan user object
$userId = $user->id; // Gunakan untuk filter data per-user
```

### 1. Lihat Daftar Barang (dengan Search & Pagination)

```
GET / (or SearchController::search())
    ↓
Dapatkan query string:
  - ?search=keyword (opsional)
  - ?page=2 (opsional, default 1)
    ↓
ItemRepository::paginate($page, $search, $userId)
    ├─ WHERE item_name LIKE "%keyword%" AND user_id = $userId
    ├─ COUNT(*) untuk total barang
    ├─ SELECT id, item_name, quantity, price, entry_date
    ├─ ORDER BY entry_date DESC, id DESC
    ├─ LIMIT 10 OFFSET ($page - 1) * 10
    │
    ├─ Return:
    │   {
    │     items: Item[],
    │     total: int,
    │     per_page: 10,
    │     current_page: int,
    │     total_pages: int
    │   }
    │
    ↓
ItemRepository::findLowStock($userId)
    ├─ WHERE quantity ≤ LOW_STOCK_THRESHOLD (5) AND user_id = $userId
    ├─ Return array of Item objects
    │
    ↓
Render views/items/index.php:
    ├─ Alert banner: tampilkan barang dengan stok rendah
    ├─ Search box: user bisa cari by nama
    ├─ Table: daftar barang
    ├─ Pagination links
    ├─ Tombol "Add" untuk barang baru
```

**File terkait:**
- [src/Controllers/SearchController.php](src/Controllers/SearchController.php)
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → `paginate()`, `findLowStock()`
- [views/items/index.php](views/items/index.php)
- [views/partials/table.php](views/partials/table.php)
- [views/partials/alert_banner.php](views/partials/alert_banner.php)

### 2. Tambah Barang Baru

```
GET /items/create
    ↓
Render views/items/create.php:
    ├─ Form dengan field:
    │   - item_name (required, max 255)
    │   - quantity (required, numeric)
    │   - price (required, decimal)
    │   - entry_date (required, date)
    │   - CSRF token (hidden)
    │
    ↓
User submit POST /items/store
    ↓
ItemController::store()
    ├─ Validasi CSRF token
    ├─ Validasi input:
    │   ├─ item_name: required, min 1, max 255
    │   ├─ quantity: required, numeric, >= 0
    │   ├─ price: required, numeric, >= 0
    │   ├─ entry_date: required, valid date
    │
    ├─ Jika ada error:
    │   ├─ Set $old = $_POST (untuk populate form kembali)
    │   ├─ Set $errors = [...messages...]
    │   └─ Re-render form dengan error
    │
    ├─ Jika valid:
    │   ├─ Sanitasi input (trim, cast type)
    │   ├─ ItemRepository::create($userId, $itemName, $quantity, $price, $entryDate)
    │   │   ├─ INSERT INTO items (user_id, item_name, quantity, price, entry_date, created_at)
    │   │   ├─ VALUES (?, ?, ?, ?, ?, NOW())
    │   │   ├─ Return $lastInsertId
    │   │
    │   └─ Redirect to /?flash=created
    │
    ↓
User lihat success message + barang di list
```

**File terkait:**
- [src/Controllers/ItemController.php](src/Controllers/ItemController.php) → `create()`, `store()`, `validate()`
- [views/items/create.php](views/items/create.php)
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → `create()`

### 3. Ubah Barang

```
GET /items/edit?id=123
    ↓
ItemController::edit()
    ├─ Dapatkan id dari query string
    ├─ ItemRepository::findById($id, $userId)
    │   ├─ SELECT * FROM items WHERE id = ? AND user_id = ?
    │   ├─ Jika tidak ditemukan: throw exception (403 Forbidden)
    │   └─ Return Item object
    │
    ├─ Render views/items/edit.php dengan data barang
    │
    ↓
User edit form + submit POST /items/update
    ↓
ItemController::update()
    ├─ Validasi CSRF token
    ├─ Dapatkan id dari POST/GET
    ├─ ItemRepository::findById($id, $userId) → ownership validation
    ├─ Validasi input (sama seperti create)
    │
    ├─ Jika valid:
    │   ├─ ItemRepository::update($id, $userId, $itemName, $quantity, $price, $entryDate)
    │   │   ├─ UPDATE items SET item_name=?, quantity=?, price=?, entry_date=?, updated_at=NOW()
    │   │   ├─ WHERE id=? AND user_id=?
    │   │
    │   └─ Redirect to /?flash=updated
    │
    ↓
User lihat success message
```

**File terkait:**
- [src/Controllers/ItemController.php](src/Controllers/ItemController.php) → `edit()`, `update()`, `resolveItem()`
- [views/items/edit.php](views/items/edit.php)
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → `update()`, `findById()`

### 4. Hapus Barang

```
User klik tombol "Delete" (dalam tabel atau form edit)
    ↓
Form submit POST /items/delete dengan:
    - id (hidden field)
    - CSRF token
    ↓
ItemController::delete()
    ├─ Validasi CSRF token
    ├─ Dapatkan id dari POST
    ├─ ItemRepository::findById($id, $userId) → ownership validation
    ├─ ItemRepository::delete($id, $userId)
    │   ├─ DELETE FROM items WHERE id=? AND user_id=?
    │
    └─ Redirect to /?flash=deleted
    ↓
User lihat success message
```

**File terkait:**
- [src/Controllers/ItemController.php](src/Controllers/ItemController.php) → `delete()`, `resolveItem()`
- [views/items/index.php](views/items/index.php) (form delete)
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → `delete()`

### 5. Low Stock Alerts

```
GET /alerts/low-stock (AJAX endpoint)
    ↓
AlertController::lowStock()
    ├─ Auth::requireAuth()
    ├─ ItemRepository::findLowStock($userId)
    │   ├─ SELECT * FROM items
    │   ├─ WHERE quantity ≤ 5 AND user_id = ?
    │   ├─ ORDER BY quantity ASC
    │
    ├─ Return JSON array of items
    │
    ↓
Frontend menampilkan barang dalam alert banner
```

**File terkait:**
- [src/Controllers/AlertController.php](src/Controllers/AlertController.php)
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → `findLowStock()`, `getLowStockThreshold()`

---

## Arsitektur Sistem

### Struktur Lapisan (Layered Architecture)

```
┌─────────────────────────────────────────┐
│         Router (index.php)              │  ← Entry point, routing
├─────────────────────────────────────────┤
│       Controllers (Request Handler)     │  ← Validasi input, business logic
│   - AuthController                     │
│   - ItemController                     │
│   - SearchController                   │
│   - AlertController                    │
├─────────────────────────────────────────┤
│    Repositories (Data Access Layer)    │  ← SQL queries, data transformation
│   - UserRepository                     │
│   - ItemRepository                     │
├─────────────────────────────────────────┤
│      Models (Domain Objects)           │  ← DTOs/Value Objects
│   - User                               │
│   - Item                               │
├─────────────────────────────────────────┤
│        Core Services                    │  ← Singleton + Utilities
│   - Database (PDO singleton)            │
│   - Auth (Static session handler)       │
│   - Autoloader                         │
├─────────────────────────────────────────┤
│          Database (MySQL)               │  ← Persistent storage
├─────────────────────────────────────────┤
│      Views (Presentation Layer)        │  ← HTML + form rendering
│   - views/auth/                        │
│   - views/items/                       │
│   - views/partials/                    │
│   - views/layout/                      │
└─────────────────────────────────────────┘
```

### Responsibility Breakdown

| Komponen                  | Tanggung Jawab                              |
|---------------------------|---------------------------------------------|
| **Router (index.php)**    | Parse URL, match routes, call controller   |
| **AuthController**        | Handle login/register/logout requests      |
| **ItemController**        | Handle CRUD requests untuk barang          |
| **SearchController**      | Handle search + pagination requests        |
| **AlertController**       | Handle low-stock alert requests            |
| **UserRepository**        | Query database untuk users + tokens        |
| **ItemRepository**        | Query database untuk items                 |
| **User Model**            | Store user data, password verification     |
| **Item Model**            | Store item data, hydration dari row        |
| **Auth Core**             | Session management, authentication state   |
| **Database Core**         | PDO singleton, connection management       |
| **Autoloader Core**       | Dynamic class loading (PSR-4 style)        |

---

## Flow Data

### Authentication Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    User Browser                             │
│         ┌──────────────────────────────────────────┐        │
│         │  Form login (username, password)         │        │
│         └──────────────┬───────────────────────────┘        │
│                        │ POST /auth/auth                    │
│                        ▼                                    │
│         ┌──────────────────────────────────────────┐        │
│         │  Server: AuthController::auth()          │        │
│         │  ├─ Validate CSRF token                  │        │
│         │  ├─ Validate input                       │        │
│         │  ├─ UserRepository::findByUsername()     │        │
│         │  ├─ User::verifyPassword()               │        │
│         │  ├─ Auth::authenticate()                 │        │
│         │  │  ├─ Set $_SESSION["auth_user_id"]    │        │
│         │  │  ├─ If rememberMe: createToken()     │        │
│         │  │  └─ Set remember-me cookie (30d)     │        │
│         │  └─ Redirect to /                        │        │
│         └──────────┬───────────────────────────────┘        │
│                    │ 302 Found + Set-Cookie                │
│                    ▼                                        │
│         ┌──────────────────────────────────────────┐        │
│         │  Session established                     │        │
│         │  Cookies: PHPSESSID, remember_token      │        │
│         └──────────────────────────────────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

### Inventory CRUD Flow Diagram

```
                   User Action
                       │
      ┌─────────────────┼─────────────────┐
      │                 │                 │
      ▼                 ▼                 ▼
   Create            Read             Update/Delete
      │                 │                 │
      ├─ Form          ├─ Query DB       ├─ Form (pre-filled)
      ├─ Validate      ├─ Paginate       ├─ Validate
      ├─ Insert DB     ├─ Search         ├─ Update DB
      └─ Redirect      └─ Render table   └─ Redirect
```

---

## Mekanisme Keamanan

### 1. CSRF Protection

**Implementasi:**
- Setiap session generate satu token CSRF: `session["csrf_token"]` (32 random bytes)
- Setiap form embed token dalam hidden field: `<input type="hidden" name="csrf_token">`
- Setiap POST/DELETE request validate token: `Auth::verifyCsrf()`

**File:**
- Generated di [index.php](index.php) line 31-33
- Verified di [src/Core/Auth.php](src/Core/Auth.php) → `verifyCsrf()`
- Rendered di [views/layout.php](views/layout.php) → `csrf()` helper

**Flow:**
```
GET /items/create (form load)
  ├─ Render hidden input dengan $_SESSION["csrf_token"]
  │
POST /items/store (form submit)
  ├─ Check $_POST["csrf_token"] === $_SESSION["csrf_token"]
  ├─ Jika tidak match: exception 403 Forbidden
```

### 2. SQL Injection Prevention

**Implementasi:**
- Gunakan prepared statements di semua query
- Parameter binding dengan type casting

**File:**
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) — all methods
- [src/Repositories/UserRepository.php](src/Repositories/UserRepository.php) — all methods

**Contoh:**
```php
// ✅ AMAN: Prepared statement
$stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? AND id = ?");
$stmt->execute([$userId, $id]);

// ❌ TIDAK AMAN: String interpolation
$sql = "SELECT * FROM items WHERE user_id = $userId AND id = $id";
```

### 3. Password Security

**Implementasi:**
- Hash password dengan `password_hash($password, PASSWORD_BCRYPT)`
- Verify dengan `password_verify($input, $hash)`

**File:**
- [src/Models/User.php](src/Models/User.php) → `hashPassword()`, `verifyPassword()`

**Flow:**
```
Registration:
  input: "Admin@12345"
  ├─ hash = password_hash("Admin@12345", PASSWORD_BCRYPT)
  ├─ INSERT users (password_hash = hash)

Login:
  input: "Admin@12345"
  ├─ hash = SELECT password_hash FROM users WHERE username = "admin"
  ├─ if password_verify("Admin@12345", hash): ✅ Success
```

### 4. Session Security

**Implementasi:**
- Configure session security pada [src/Core/Auth.php](src/Core/Auth.php) → `configureSession()`

**Settings:**
```php
session_set_cookie_params([
    'secure' => true,      // HTTPS only
    'httponly' => true,    // No JavaScript access
    'samesite' => 'Lax',   // Prevent CSRF
]);
```

### 5. Access Control (Ownership Validation)

**Implementasi:**
- Setiap query item include `WHERE user_id = ?`
- Mencegah user melihat/edit barang milik user lain

**File:**
- [src/Controllers/ItemController.php](src/Controllers/ItemController.php) → `resolveItem()`
- [src/Repositories/ItemRepository.php](src/Repositories/ItemRepository.php) → all methods

**Flow:**
```
GET /items/edit?id=99
  ├─ ItemController::edit()
  ├─ resolveItem() → ItemRepository::findById($id, $currentUserId)
  ├─ SELECT * FROM items WHERE id = 99 AND user_id = $currentUserId
  ├─ Jika tidak ditemukan: 404 atau redirect (user lain tidak bisa access)
```

### 6. Remember-Me Token Security

**Implementasi:**
- Token di-generate sebagai random bytes
- Token di-hash sebelum disimpan di database
- Token di-rotate setiap kali digunakan
- Token memiliki expiry time

**File:**
- [src/Core/Auth.php](src/Core/Auth.php) → `setRememberCookie()`, `clearRememberCookie()`
- [src/Repositories/UserRepository.php](src/Repositories/UserRepository.php) → `verifyRememberToken()`

**Flow:**
```
setRememberCookie():
  ├─ token = bin2hex(random_bytes(32))  // 64 char random
  ├─ tokenHash = hash('sha256', token)  // Simpan hash
  ├─ INSERT remember_tokens (user_id, token_hash, expires_at = now + 30 days)
  ├─ SET-COOKIE remember_token = token (HttpOnly, Secure, 30d)

verifyRememberToken():
  ├─ $cookie = $_COOKIE["remember_token"]
  ├─ SELECT * FROM remember_tokens WHERE token_hash = hash($cookie)
  ├─ Jika valid & not expired:
  │   ├─ DELETE token lama
  │   ├─ Generate token baru (token rotation)
  │   ├─ Return { user, newToken }
```

---

## Penanganan Error

### 1. Validasi Input

**Server-side validation** untuk semua input:

```php
// Field: item_name
if (empty($_POST["item_name"]) || strlen($_POST["item_name"]) > 255) {
    $errors["item_name"] = "Nama barang tidak boleh kosong atau melebihi 255 karakter";
}

// Field: quantity
if (!is_numeric($_POST["quantity"]) || $_POST["quantity"] < 0) {
    $errors["quantity"] = "Jumlah harus berupa angka positif";
}

// Jika ada error, re-render form dengan old value
if (!empty($errors)) {
    $old = $_POST;
    require __DIR__ . "/../../views/items/create.php";
    return;
}
```

**File:**
- [src/Controllers/ItemController.php](src/Controllers/ItemController.php) → `validate()`
- [src/Controllers/AuthController.php](src/Controllers/AuthController.php) → validation logic

### 2. 404 & Error Handling

**404 Not Found:**
- Router default case menampilkan [views/404.php](views/404.php)
- Owner validation di ItemController → redirect 404

**Database Errors:**
- PDO exception di [src/Core/Database.php](src/Core/Database.php) dipangkap dan diganti generic error message
- Error detail tidak di-expose ke user (security)

```php
try {
    $this->pdo = new PDO($dsn, $config["user"], $config["pass"], [...]);
} catch (PDOException $e) {
    throw new \RuntimeException("Database connection failed.", previous: $e);
}
```

### 3. Access Denied

**401 Unauthorized** (Not Authenticated):
- `Auth::requireAuth()` → redirect ke /auth/login

**403 Forbidden** (Not Authorized):
- CSRF token mismatch → exception
- Item ownership mismatch → 404 atau redirect

```php
public function resolveItem(): Item {
    $item = $this->repo->findById($itemId, $userId);
    if ($item === null) {
        http_response_code(404);
        require __DIR__ . "/../../views/404.php";
        exit;
    }
    return $item;
}
```

---

## Checklist Keamanan Aplikasi

- [x] CSRF protection pada semua form
- [x] Prepared statements untuk semua query
- [x] Password hashing dengan Bcrypt
- [x] Session security configuration
- [x] Ownership validation untuk item
- [x] Remember-me token rotation
- [x] HttpOnly cookies
- [x] PDO error handling (no leakage)
- [x] Input validation server-side
- [x] Access control (Auth::requireAuth)

---

## Kesimpulan

Aplikasi ini menerapkan **security best practices** modern dengan:

1. **Defense in depth** — multiple layers of security
2. **Least privilege** — users hanya access data mereka sendiri
3. **Secure defaults** — session security pre-configured
4. **Input validation** — server-side checking
5. **Error handling** — graceful degradation tanpa info leak

Untuk lebih lanjut tentang pengembangan, lihat [Readme.md](Readme.md).
