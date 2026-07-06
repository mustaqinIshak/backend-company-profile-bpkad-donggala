# Backend API – Company Profile & Sistem Internal BPKAD Kabupaten Donggala

REST API berbasis **Laravel 11** + **Laravel Sanctum** untuk website company profile dan sistem internal BPKAD Kabupaten Donggala.

Mencakup tiga modul utama:
1. **Company Profile** — profil instansi, berita, layanan, jumbotron, organisasi
2. **Manajemen Tamu Loby** — registrasi tamu, antrian, dan tracking kunjungan
3. **Persuratan** — surat masuk, surat keluar, dan disposisi (ala Srikandi)

## Requirements

| Tool | Versi |
|------|-------|
| PHP | ≥ 8.2 |
| Composer | ≥ 2.x |
| Database | SQLite / MySQL / PostgreSQL |

## Instalasi

```bash
# 1. Clone repository
git clone <repo-url>
cd backend-company-profile-bpkad-donggala

# 2. Install dependencies
composer install

# 3. Salin environment file
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Buat file database SQLite (atau konfigurasi MySQL di .env)
touch database/database.sqlite

# 6. Jalankan migrasi dan seeder
php artisan migrate --seed

# 7. Buat symlink storage
php artisan storage:link

# 8. Jalankan development server
php artisan serve
```

## Konfigurasi .env

```env
DB_CONNECTION=sqlite               # atau mysql

# Jika MySQL:
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bpkad_donggala
DB_USERNAME=root
DB_PASSWORD=secret

# Admin default (digunakan saat seeding)
ADMIN_EMAIL=admin@bpkad-donggala.go.id
ADMIN_NAME=Administrator
ADMIN_PASSWORD=admin123
```

## Sistem Role (RBAC)

Setiap user admin memiliki satu atau lebih role. Role dikontrol via middleware `role:nama_role`.

| Role | Display Name | Akses |
|------|-------------|-------|
| `super_admin` | Super Administrator | Akses penuh ke semua fitur |
| `admin` | Administrator | Kelola konten: profil, berita, layanan, jumbotron, organisasi, kontak |
| `resepsionis` | Resepsionis | Kelola tamu loby dan kontak masuk |
| `petugas_surat` | Petugas Persuratan | Kelola surat masuk, surat keluar, disposisi |
| `pimpinan` | Pimpinan | Lihat surat, setujui surat keluar, buat & lihat disposisi |

### Manajemen Role via API

Gunakan endpoint `/auth/me` untuk melihat role aktif user yang sedang login.

Untuk assign/cabut role ke admin, akses langsung database atau buat endpoint admin management tersendiri. Pivot table: `admin_role` (kolom: `admin_id`, `role_id`).

---

## Autentikasi

Login menggunakan endpoint `POST /api/auth/login`:

```json
{
  "email": "admin@bpkad-donggala.go.id",
  "password": "admin123"
}
```

Response berisi `token`. Gunakan sebagai Bearer token pada semua request protected:
```
Authorization: Bearer <token>
```

---

## Alur Tamu Loby

```
Tamu datang → POST /tamu (public, dapat foto opsional)
    ↓
status: menunggu  ←─ nomor antrian auto-generate (A001, A002, ...)
    ↓
Resepsionis terima → PATCH /admin/tamu/{id}/status  { status: "diterima" }
    atau
Resepsionis tolak  → PATCH /admin/tamu/{id}/status  { status: "ditolak" }
    ↓
Tamu selesai keluar → PATCH /admin/tamu/{id}/checkout
    ↓
status: selesai  ← waktu_keluar otomatis diisi
```

---

## Alur Persuratan

### Surat Masuk
```
Terima fisik → POST /admin/surat-masuk
    ↓ no_agenda auto: SM/2026/001
status: baru
    ↓
Buat disposisi → POST /admin/surat-masuk/{id}/disposisi
    ↓
status: diproses  ← auto-update saat disposisi pertama dibuat
    ↓
Penerima disposisi proses → PATCH .../disposisi/{id}/status  { status: "sedang_diproses" }
    ↓
Penerima balas → PATCH .../disposisi/{id}/balas  { catatan_balasan: "..." }
    ↓
Selesaikan disposisi → PATCH .../disposisi/{id}/status  { status: "selesai" }
    ↓
status surat: selesai  ← auto-update saat semua disposisi selesai
    ↓
Arsipkan → PATCH /admin/surat-masuk/{id}/status  { status: "arsip" }
```

### Surat Keluar
```
Petugas buat draft → POST /admin/surat-keluar
    ↓ no_agenda auto: SK/2026/001
status: draft
    ↓
Ajukan ke pimpinan → PATCH /admin/surat-keluar/{id}/ajukan
    ↓
status: menunggu_persetujuan
    ↓
Pimpinan setujui → PATCH /admin/surat-keluar/{id}/setujui  { nomor_surat, tanggal_surat }
    ↓
status: disetujui
    ↓
Petugas kirim → PATCH /admin/surat-keluar/{id}/kirim
    ↓
status: dikirim  ← tanggal_kirim otomatis diisi
    ↓
Arsipkan → PATCH /admin/surat-keluar/{id}/arsip
    ↓
status: arsip
```

---

## Endpoint API

Base URL: `http://localhost:8000/api`

### 🔓 Public

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `POST` | `/auth/login` | Login admin |
| `GET` | `/profile` | Profil instansi |
| `GET` | `/jumbotron` | Daftar slide jumbotron |
| `GET` | `/organisasi` | Daftar semua bidang organisasi |
| `GET` | `/organisasi/bidang/{bidang}` | Bidang tertentu (sekretariat/aset/perbendaharaan/akuntansi/anggaran) |
| `GET` | `/organisasi/{id}/jabatan` | Jabatan dalam suatu bidang |
| `GET` | `/berita` | Daftar berita (paginasi) |
| `GET` | `/berita/slug/{slug}` | Berita by slug |
| `GET` | `/berita/{id}` | Detail berita |
| `GET` | `/layanan` | Daftar layanan |
| `GET` | `/layanan/{id}` | Detail layanan |
| `POST` | `/kontak` | Kirim pesan kontak |
| `POST` | `/tamu` | Registrasi tamu loby (foto opsional) |

### 🔒 Auth (semua role)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/auth/me` | Info admin + roles yang sedang login |
| `POST` | `/auth/change-password` | Ubah password |
| `POST` | `/auth/logout` | Logout |

### 🔒 Admin Management (role: super_admin)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/admin/roles` | Daftar semua role yang tersedia |
| `GET` | `/admin/admins` | Daftar semua admin (filter: search, role) |
| `POST` | `/admin/admins` | Buat admin baru (body: name, email, password, roles[]) |
| `GET` | `/admin/admins/{id}` | Detail admin beserta roles |
| `POST` | `/admin/admins/{id}` | Update nama/email/password admin |
| `PUT` | `/admin/admins/{id}/roles` | Sync (replace) role admin (body: roles[]) |
| `DELETE` | `/admin/admins/{id}` | Hapus admin (tidak dapat menghapus diri sendiri) |

### 🔒 Konten (role: admin, super_admin)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `POST` | `/admin/profile` | Update profil instansi |
| `POST` | `/admin/jumbotron` | Tambah slide |
| `POST` | `/admin/jumbotron/{id}` | Update slide |
| `DELETE` | `/admin/jumbotron/{id}` | Hapus slide |
| `PATCH` | `/admin/jumbotron/{id}/toggle` | Toggle aktif/nonaktif |
| `PUT` | `/admin/organisasi/bidang/{bidang}` | Simpan data bidang |
| `POST` | `/admin/organisasi/{id}/jabatan` | Tambah jabatan |
| `POST` | `/admin/organisasi/{id}/jabatan/{jabId}` | Update jabatan |
| `DELETE` | `/admin/organisasi/{id}/jabatan/{jabId}` | Hapus jabatan |
| `POST` | `/admin/berita` | Tambah berita |
| `POST` | `/admin/berita/{id}` | Update berita |
| `DELETE` | `/admin/berita/{id}` | Hapus berita |
| `POST` | `/admin/layanan` | Tambah layanan |
| `POST` | `/admin/layanan/{id}` | Update layanan |
| `DELETE` | `/admin/layanan/{id}` | Hapus layanan |

### 🔒 Kontak (role: admin, super_admin, resepsionis)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/admin/kontak` | Daftar pesan masuk |
| `GET` | `/admin/kontak/{id}` | Detail pesan |
| `PATCH` | `/admin/kontak/{id}/status` | Update status pesan |
| `DELETE` | `/admin/kontak/{id}` | Hapus pesan |

### 🔒 Tamu Loby (role: resepsionis, admin, super_admin)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/admin/tamu` | Daftar tamu (filter: tanggal, status, search) |
| `GET` | `/admin/tamu/{id}` | Detail tamu |
| `PATCH` | `/admin/tamu/{id}/status` | Update status: diterima / ditolak / selesai |
| `PATCH` | `/admin/tamu/{id}/checkout` | Checkout tamu (isi waktu_keluar) |
| `DELETE` | `/admin/tamu/{id}` | Hapus data tamu |

### 🔒 Surat Masuk (role: petugas_surat, admin, super_admin)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/admin/surat-masuk` | Daftar surat masuk (filter: status, tahun, search) |
| `POST` | `/admin/surat-masuk` | Catat surat masuk baru |
| `GET` | `/admin/surat-masuk/{id}` | Detail surat masuk + disposisi |
| `POST` | `/admin/surat-masuk/{id}` | Update surat masuk |
| `PATCH` | `/admin/surat-masuk/{id}/status` | Update status surat |
| `DELETE` | `/admin/surat-masuk/{id}` | Hapus surat masuk |

### 🔒 Disposisi (role: petugas_surat, pimpinan, admin, super_admin)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/admin/surat-masuk/{id}/disposisi` | Daftar disposisi surat |
| `POST` | `/admin/surat-masuk/{id}/disposisi` | Buat disposisi baru |
| `GET` | `/admin/surat-masuk/{id}/disposisi/{dispId}` | Detail disposisi |
| `PATCH` | `/admin/surat-masuk/{id}/disposisi/{dispId}/status` | Update status disposisi |
| `PATCH` | `/admin/surat-masuk/{id}/disposisi/{dispId}/balas` | Balas disposisi (hanya penerima) |
| `DELETE` | `/admin/surat-masuk/{id}/disposisi/{dispId}` | Hapus disposisi |

### 🔒 Surat Keluar

| Method | URL | Role | Deskripsi |
|--------|-----|------|-----------|
| `GET` | `/admin/surat-keluar` | petugas_surat, admin, super_admin | Daftar surat keluar |
| `POST` | `/admin/surat-keluar` | petugas_surat, admin, super_admin | Buat draft surat keluar |
| `GET` | `/admin/surat-keluar/{id}` | petugas_surat, admin, super_admin | Detail surat keluar |
| `POST` | `/admin/surat-keluar/{id}` | petugas_surat, admin, super_admin | Update surat (hanya draft) |
| `PATCH` | `/admin/surat-keluar/{id}/ajukan` | petugas_surat, admin, super_admin | Ajukan ke pimpinan |
| `PATCH` | `/admin/surat-keluar/{id}/setujui` | pimpinan, super_admin | Setujui surat + isi nomor resmi |
| `PATCH` | `/admin/surat-keluar/{id}/kirim` | petugas_surat, admin, super_admin | Tandai surat terkirim |
| `PATCH` | `/admin/surat-keluar/{id}/arsip` | petugas_surat, admin, super_admin | Arsipkan surat |
| `DELETE` | `/admin/surat-keluar/{id}` | petugas_surat, admin, super_admin | Hapus surat (hanya draft/menunggu) |

---

## Upload File

| Jenis | Endpoint | Format | Maks. |
|-------|----------|--------|-------|
| Gambar profil, logo | `/admin/profile` | JPG, JPEG, PNG, WEBP | 2 MB (logo), 5 MB (struktur) |
| Gambar jumbotron | `/admin/jumbotron` | JPG, JPEG, PNG, WEBP | 2 MB |
| Foto jabatan | `/admin/organisasi/…/jabatan` | JPG, JPEG, PNG, WEBP | 2 MB |
| Gambar berita | `/admin/berita` | JPG, JPEG, PNG, WEBP | 2 MB |
| Dokumen layanan | `/admin/layanan` | PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX | 20 MB |
| Foto tamu | `POST /tamu` | JPG, JPEG, PNG, WEBP | 2 MB (opsional) |
| File surat | `/admin/surat-masuk`, `/admin/surat-keluar` | PDF, DOC, DOCX | 10 MB |

Semua file tersimpan di `storage/app/public/` dan diakses via `/storage/`.

---

## Struktur Tabel Baru

| Tabel | Deskripsi |
|-------|-----------|
| `roles` | Daftar role: super_admin, admin, resepsionis, petugas_surat, pimpinan |
| `admin_role` | Pivot relasi admin ↔ role (many-to-many) |
| `tamus` | Data tamu loby dengan nomor antrian harian otomatis |
| `surat_masuks` | Surat masuk dari eksternal, no. agenda otomatis SM/YYYY/NNN |
| `surat_keluars` | Surat keluar, no. agenda otomatis SK/YYYY/NNN |
| `disposisis` | Disposisi / pelimpahan surat masuk ke pejabat |


## Requirements

| Tool | Versi |
|------|-------|
| PHP | ≥ 8.2 |
| Composer | ≥ 2.x |
| Database | SQLite / MySQL / PostgreSQL |

## Instalasi

```bash
# 1. Clone repository
git clone <repo-url>
cd backend-company-profile-bpkad-donggala

# 2. Install dependencies
composer install

# 3. Salin environment file
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Buat file database SQLite (atau konfigurasi MySQL di .env)
touch database/database.sqlite

# 6. Jalankan migrasi dan seeder
php artisan migrate --seed

# 7. Buat symlink storage
php artisan storage:link

# 8. Jalankan development server
php artisan serve
```

## Konfigurasi .env

```env
DB_CONNECTION=sqlite               # atau mysql

# Jika MySQL:
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bpkad_donggala
DB_USERNAME=root
DB_PASSWORD=secret

# Admin default (digunakan saat seeding)
ADMIN_EMAIL=admin@bpkad-donggala.go.id
ADMIN_PASSWORD=admin123
```

## Endpoint API

Base URL: `http://localhost:8000/api`

### 🔓 Public

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `POST` | `/auth/login` | Login admin |
| `GET` | `/profile` | Profil instansi |
| `GET` | `/jumbotron` | Daftar slide jumbotron |
| `GET` | `/organisasi` | Daftar semua bidang organisasi |
| `GET` | `/organisasi/bidang/{bidang}` | Bidang tertentu (sekretariat/aset/perbendaharaan/akuntansi/anggaran) |
| `GET` | `/organisasi/{id}/jabatan` | Jabatan dalam suatu bidang |
| `GET` | `/berita` | Daftar berita (paginasi) |
| `GET` | `/berita/slug/{slug}` | Berita by slug |
| `GET` | `/berita/{id}` | Detail berita |
| `GET` | `/layanan` | Daftar layanan |
| `GET` | `/layanan/{id}` | Detail layanan |
| `POST` | `/kontak` | Kirim pesan kontak |

### 🔒 Admin (Bearer Token)

| Method | URL | Deskripsi |
|--------|-----|-----------|
| `GET` | `/auth/me` | Info admin login |
| `POST` | `/auth/change-password` | Ubah password |
| `POST` | `/auth/logout` | Logout |
| `POST` | `/admin/profile` | Update profil instansi |
| `POST` | `/admin/jumbotron` | Tambah slide |
| `POST` | `/admin/jumbotron/{id}` | Update slide |
| `DELETE` | `/admin/jumbotron/{id}` | Hapus slide |
| `PATCH` | `/admin/jumbotron/{id}/toggle` | Toggle aktif/nonaktif |
| `PUT` | `/admin/organisasi/bidang/{bidang}` | Simpan data bidang |
| `POST` | `/admin/organisasi/{id}/jabatan` | Tambah jabatan |
| `POST` | `/admin/organisasi/{id}/jabatan/{jabId}` | Update jabatan |
| `DELETE` | `/admin/organisasi/{id}/jabatan/{jabId}` | Hapus jabatan |
| `POST` | `/admin/berita` | Tambah berita |
| `POST` | `/admin/berita/{id}` | Update berita |
| `DELETE` | `/admin/berita/{id}` | Hapus berita |
| `POST` | `/admin/layanan` | Tambah layanan |
| `POST` | `/admin/layanan/{id}` | Update layanan |
| `DELETE` | `/admin/layanan/{id}` | Hapus layanan |
| `GET` | `/admin/kontak` | Daftar pesan masuk |
| `GET` | `/admin/kontak/{id}` | Detail pesan |
| `PATCH` | `/admin/kontak/{id}/status` | Update status pesan |
| `DELETE` | `/admin/kontak/{id}` | Hapus pesan |

## Autentikasi

Login menggunakan endpoint `POST /api/auth/login`:

```json
{
  "email": "admin@bpkad-donggala.go.id",
  "password": "admin123"
}
```

Response berisi `token`. Gunakan sebagai Bearer token:
```
Authorization: Bearer <token>
```

## Upload File

- **Gambar** (logo, jumbotron, berita, foto jabatan): JPG, JPEG, PNG, WEBP – maks. 2 MB (logo profil 2 MB, struktur org 5 MB)
- **Dokumen layanan**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX – maks. 20 MB
- File tersimpan di `storage/app/public/` dan diakses via `/storage/`

## Perubahan PHP Biasa (Controller, Model, Route, dll)

Karena ada volume mount **.:/var/www/html**, perubahan langsung aktif tanpa restart.

Tapi jika route/config di-cache, harus clear dulu:

```bash
docker compose exec app php artisan route:clear
docker compose exec app php artisan config:clear
```

## Perubahan **.env**
```bash
docker compose restart app
```

## Perubahan Migration (tambah tabel/kolom baru)
```bash
docker compose exec app php artisan migrate
```

## Tambah Package baru (**composer require ...**)

Image harus di-rebuild karena composer install ada di Dockerfile:

```bash
docker compose up -d --build app
```

## Perbuahan Dockerfile atau docker-composer.yml
```bash
docker compose up -d --buil
```

## Peubahan **default.conf**
```bash
docker compose restart nginx
```

## Ringkasan Cepat
| Jenis Perubahan | Perintah |
|-----------------|----------|
| File PHP (controller, model, dll.) | Langsung aktif ✅
| .env | **docker compose restart app** |
| Migration baru | **docker compose exec app php** **artisan migrate** |
| composer.json / package baru | **docker compose up -d --build app** |
| Dockerfile | **docker compose up -d --build** |
| nginx/default.conf | **docker compose restart nginx** |