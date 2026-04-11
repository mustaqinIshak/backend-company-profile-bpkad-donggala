# Backend API – Company Profile BPKAD Kabupaten Donggala

REST API berbasis **Laravel 11** + **Laravel Sanctum** untuk website company profile BPKAD Kabupaten Donggala.

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
