# 📋 Dokumentasi Test Cases - E-Office App

Dokumen ini berisi ringkasan lengkap semua test cases yang tersedia dalam aplikasi. Dibuat agar tim developer dapat dengan mudah memahami cakupan testing dan menambahkan test baru sesuai pola yang ada.

---

## 📊 Ringkasan

| Kategori | Jumlah Tests | Assertions |
|----------|--------------|------------|
| Unit Tests | 21 | 40 |
| Feature Tests | 65 | 279 |
| **Total** | **86** | **319** |

---

## 🧪 Unit Tests

Unit tests berfokus pada pengujian komponen individual (model, helper, dll) secara terisolasi.

### 📁 `tests/Unit/Models/DivisionTest.php`

Test untuk model **Division** (Divisi).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it has correct fillable attributes` | Memastikan atribut yang bisa diisi (name, description, is_active) sudah benar |
| 2 | `it casts is_active to boolean` | Memastikan field is_active otomatis dikonversi ke tipe boolean |
| 3 | `it has many users relationship` | Memastikan relasi Division → Users (one-to-many) berfungsi |
| 4 | `it can create division with factory` | Memastikan factory bisa membuat data Division dengan benar |
| 5 | `it can create inactive division with factory state` | Memastikan factory state `inactive()` menghasilkan Division tidak aktif |

---

### 📁 `tests/Unit/Models/PositionTest.php`

Test untuk model **Position** (Jabatan).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it has correct fillable attributes` | Memastikan atribut yang bisa diisi (name, description, is_active) sudah benar |
| 2 | `it casts is_active to boolean` | Memastikan field is_active otomatis dikonversi ke tipe boolean |
| 3 | `it has many users relationship` | Memastikan relasi Position → Users (one-to-many) berfungsi |
| 4 | `it can create position with factory` | Memastikan factory bisa membuat data Position dengan benar |
| 5 | `it can create inactive position with factory state` | Memastikan factory state `inactive()` menghasilkan Position tidak aktif |

---

### 📁 `tests/Unit/Models/UserTest.php`

Test untuk model **User** (Pengguna).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it has correct fillable attributes` | Memastikan atribut yang bisa diisi sudah benar (name, email, password, dll) |
| 2 | `it casts is_active to boolean` | Memastikan field is_active otomatis dikonversi ke tipe boolean |
| 3 | `it hashes password automatically` | Memastikan password otomatis di-hash, tidak disimpan plain text |
| 4 | `it hides password and remember_token in serialization` | Memastikan password & token tidak bocor saat model di-serialize ke JSON |
| 5 | `it belongs to division relationship` | Memastikan relasi User → Division (many-to-one) berfungsi |
| 6 | `it belongs to position relationship` | Memastikan relasi User → Position (many-to-one) berfungsi |
| 7 | `it can have division and position` | Memastikan user bisa memiliki divisi dan jabatan sekaligus |
| 8 | `it generates initials correctly` | Memastikan accessor `initials` menghasilkan inisial nama dengan benar (John Doe → JD) |
| 9 | `it generates initials for single name` | Memastikan inisial benar untuk nama tunggal (John → J) |
| 10 | `it limits initials to two characters` | Memastikan inisial maksimal 2 karakter (John Doe Smith → JD) |
| 11 | `it can create inactive user with factory state` | Memastikan factory state `inactive()` menghasilkan User tidak aktif |

---

## 🔗 Feature/Integration Tests

Feature tests menguji fitur secara end-to-end, termasuk HTTP request, database, dan response.

### 📁 `tests/Feature/Auth/AuthenticationTest.php`

Test untuk fitur **Authentication** (Login/Logout).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it can display login page` | Halaman login bisa diakses dan menampilkan komponen yang benar |
| 2 | `it can authenticate with valid credentials` | User bisa login dengan email & password yang benar |
| 3 | `it cannot authenticate with invalid email` | Login gagal jika email salah, dan menampilkan error |
| 4 | `it cannot authenticate with invalid password` | Login gagal jika password salah, dan menampilkan error |
| 5 | `it requires email for login` | Validasi: email wajib diisi saat login |
| 6 | `it requires password for login` | Validasi: password wajib diisi saat login |
| 7 | `it can logout authenticated user` | User yang sudah login bisa logout dan di-redirect ke halaman login |
| 8 | `it redirects guest to login when accessing dashboard` | Guest (belum login) di-redirect ke login saat akses dashboard |
| 9 | `it allows authenticated user to access dashboard` | User yang sudah login bisa akses dashboard |
| 10 | `it redirects root path to login` | Akses "/" di-redirect ke halaman login |

---

### 📁 `tests/Feature/Division/DivisionTest.php`

Test untuk fitur **CRUD Division** (Divisi).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it can display division index page` | Halaman daftar divisi bisa diakses oleh user dengan role Superadmin |
| 2 | `it can display division create page` | Halaman form tambah divisi bisa diakses |
| 3 | `it can create a new division` | Bisa membuat divisi baru, data tersimpan di database |
| 4 | `it requires name when creating division` | Validasi: nama divisi wajib diisi |
| 5 | `it can display division edit page` | Halaman form edit divisi bisa diakses dan menampilkan data divisi |
| 6 | `it can update an existing division` | Bisa mengupdate divisi, perubahan tersimpan di database |
| 7 | `it can delete a division` | Bisa menghapus divisi, data terhapus dari database |
| 8 | `it can fetch divisions datatable` | API datatable mengembalikan data dengan struktur pagination yang benar |
| 9 | `it can search divisions in datatable` | Fitur pencarian di datatable berfungsi dengan benar |
| 10 | `it can paginate divisions in datatable` | Fitur pagination di datatable berfungsi dengan benar |
| 11 | `it prevents unauthenticated access to divisions` | Guest tidak bisa akses halaman divisi (di-redirect ke login) |

---

### 📁 `tests/Feature/Position/PositionTest.php`

Test untuk fitur **CRUD Position** (Jabatan).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it can display position index page` | Halaman daftar jabatan bisa diakses oleh user dengan role Superadmin |
| 2 | `it can display position create page` | Halaman form tambah jabatan bisa diakses |
| 3 | `it can create a new position` | Bisa membuat jabatan baru, data tersimpan di database |
| 4 | `it requires name when creating position` | Validasi: nama jabatan wajib diisi |
| 5 | `it can display position edit page` | Halaman form edit jabatan bisa diakses dan menampilkan data jabatan |
| 6 | `it can update an existing position` | Bisa mengupdate jabatan, perubahan tersimpan di database |
| 7 | `it can delete a position` | Bisa menghapus jabatan, data terhapus dari database |
| 8 | `it can fetch positions datatable` | API datatable mengembalikan data dengan struktur pagination yang benar |
| 9 | `it can search positions in datatable` | Fitur pencarian di datatable berfungsi dengan benar |
| 10 | `it can paginate positions in datatable` | Fitur pagination di datatable berfungsi dengan benar |
| 11 | `it prevents unauthenticated access to positions` | Guest tidak bisa akses halaman jabatan (di-redirect ke login) |

---

### 📁 `tests/Feature/User/UserTest.php`

Test untuk fitur **CRUD User** (Pengguna).

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| 1 | `it can display user index page` | Halaman daftar pengguna bisa diakses oleh user dengan role Superadmin |
| 2 | `it can display user create page` | Halaman form tambah pengguna bisa diakses, membawa data divisions, positions, roles |
| 3 | `it can create a new user` | Bisa membuat pengguna baru dengan divisi, jabatan, dan role |
| 4 | `it requires name when creating user` | Validasi: nama wajib diisi |
| 5 | `it requires valid email when creating user` | Validasi: email harus format yang valid |
| 6 | `it requires unique email when creating user` | Validasi: email harus unik (tidak boleh duplikat) |
| 7 | `it can display user edit page` | Halaman form edit pengguna bisa diakses dan menampilkan data lengkap |
| 8 | `it can update an existing user` | Bisa mengupdate pengguna, perubahan tersimpan di database |
| 9 | `it can update user without changing password` | Update pengguna tanpa mengubah password (password tetap sama) |
| 10 | `it can delete a user` | Bisa menghapus pengguna, data terhapus dari database |
| 11 | `it can fetch users datatable` | API datatable mengembalikan data dengan struktur pagination yang benar |
| 12 | `it can search users in datatable` | Fitur pencarian berdasarkan nama berfungsi |
| 13 | `it can search users by email in datatable` | Fitur pencarian berdasarkan email berfungsi |
| 14 | `it can paginate users in datatable` | Fitur pagination di datatable berfungsi dengan benar |
| 15 | `it includes user relationships in datatable` | Data user di datatable menyertakan relasi division dan position |
| 16 | `it prevents unauthenticated access to users` | Guest tidak bisa akses halaman pengguna (di-redirect ke login) |

---

### 📁 `tests/Feature/Profile/ProfileTest.php`

Test untuk fitur **Profile Management** (Edit Profil & Password).

| No | Test Case | Deskripsi |
|----|-----------|-----------| 
| 1 | `it can display edit profile page` | Halaman edit profil bisa diakses oleh user yang sudah login |
| 2 | `it prevents unauthenticated access to profile page` | Guest tidak bisa akses halaman profil (di-redirect ke login) |
| 3 | `it can update profile with valid data` | Bisa mengupdate profil (name, email, phone, address) dengan data yang valid |
| 4 | `it requires name when updating profile` | Validasi: nama wajib diisi saat update profil |
| 5 | `it requires valid email when updating profile` | Validasi: email harus format yang valid |
| 6 | `it requires unique email when updating profile` | Validasi: email harus unik (tidak boleh duplikat dengan user lain) |
| 7 | `it allows user to keep their own email when updating profile` | User boleh tetap menggunakan email yang sama saat update |
| 8 | `it validates phone max length when updating profile` | Validasi: nomor telepon maksimal 20 karakter |
| 9 | `it validates address max length when updating profile` | Validasi: alamat maksimal 500 karakter |
| 10 | `it can display edit password page` | Halaman ganti password bisa diakses oleh user yang sudah login |
| 11 | `it prevents unauthenticated access to password page` | Guest tidak bisa akses halaman ganti password (di-redirect ke login) |
| 12 | `it can update password with valid data` | Bisa mengubah password dengan data yang valid |
| 13 | `it requires current password when updating password` | Validasi: password saat ini wajib diisi |
| 14 | `it validates current password is correct when updating password` | Validasi: password saat ini harus benar |
| 15 | `it requires password confirmation when updating password` | Validasi: konfirmasi password harus cocok |
| 16 | `it requires new password when updating password` | Validasi: password baru wajib diisi |
| 17 | `it prevents unauthenticated access to update password` | Guest tidak bisa akses endpoint update password (di-redirect ke login) |

## 🚀 Cara Menjalankan Tests

### Menjalankan Semua Tests
```bash
php artisan test
```

### Menjalankan Unit Tests Saja
```bash
php artisan test tests/Unit
```

### Menjalankan Feature Tests Saja
```bash
php artisan test tests/Feature
```

### Menjalankan Test File Tertentu
```bash
php artisan test tests/Feature/Auth/AuthenticationTest.php
php artisan test tests/Feature/Division/DivisionTest.php
php artisan test tests/Feature/Position/PositionTest.php
php artisan test tests/Feature/User/UserTest.php
php artisan test tests/Feature/Profile/ProfileTest.php
```

### Menjalankan Test dengan Filter Nama
```bash
php artisan test --filter="can create a new user"
```

### Menjalankan Test dengan Output Detail
```bash
php artisan test --verbose
```

---

## 📝 Panduan Menambah Test Baru

### 1. Untuk Unit Test Model Baru

Buat file di `tests/Unit/Models/NamaModelTest.php`:

```php
<?php

use App\Models\NamaModel;

it('has correct fillable attributes', function () {
    $model = new NamaModel;
    expect($model->getFillable())->toBe(['field1', 'field2']);
});

it('can create with factory', function () {
    $model = NamaModel::factory()->create();
    expect($model)->toBeInstanceOf(NamaModel::class);
});
```

### 2. Untuk Feature Test CRUD Baru

Buat file di `tests/Feature/NamaModule/NamaModuleTest.php`:

```php
<?php

use App\Models\User;
use App\Models\NamaModel;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('can display index page', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->get('/nama-route');
    $response->assertStatus(200);
});

it('can create a new record', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $response = $this->actingAs($user)->post('/nama-route/store', [
        'field1' => 'value1',
        'field2' => 'value2',
    ]);

    $response->assertRedirect('/nama-route');
    $this->assertDatabaseHas('nama_tables', [
        'field1' => 'value1',
    ]);
});
```

---

## ✅ Checklist Sebelum Push Code

- [ ] Semua tests lulus (`php artisan test`)
- [ ] Tidak ada test yang di-skip tanpa alasan
- [ ] Test baru sudah ditambahkan untuk fitur baru
- [ ] Coverage untuk happy path dan error cases

---

*Terakhir diupdate: 11 Desember 2024*
