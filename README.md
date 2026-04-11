# Backend Company Profile BPKAD Kabupaten Donggala

REST API untuk Company Profile **Badan Pengelola Keuangan dan Aset Daerah (BPKAD) Kabupaten Donggala** yang dibangun menggunakan **Node.js**, **Express.js**, dan **Sequelize ORM**.

## Fitur API

- **Profil** – Visi, misi, dan informasi instansi
- **Jumbotron** – Upload dan tampil slide gambar utama
- **Organisasi** – Manajemen bidang (Sekretariat, Aset, Perbendaharaan, Akuntansi, Anggaran) beserta jabatan & pejabat
- **Berita** – Upload dan tampil berita kegiatan
- **Layanan** – Upload dokumen layanan per tahun APBD (Penganggaran, Penatausahaan, Pelaporan & Pertanggungjawaban, Perencanaan Evaluasi, Perjanjian Kinerja)
- **Kontak** – Formulir pesan kontak masyarakat
- **Autentikasi** – Login admin menggunakan JWT

## Teknologi

- Node.js + Express.js
- Sequelize ORM (SQLite untuk dev, MySQL untuk produksi)
- Multer (file upload)
- JWT (autentikasi admin)
- bcryptjs (enkripsi password)

## Instalasi

```bash
# 1. Clone & masuk ke folder
git clone <repo-url>
cd backend-company-profile-bpkad-donggala

# 2. Install dependencies
npm install

# 3. Salin file .env
cp .env.example .env

# 4. Sesuaikan konfigurasi di .env

# 5. Jalankan server
npm run dev   # development (nodemon)
npm start     # production
```

Server berjalan di `http://localhost:3000`. Admin default: `username=admin`, `password=admin123`.

## Struktur Folder

```
├── index.js              # Entry point
├── src/
│   ├── app.js            # Express app
│   ├── config/database.js
│   ├── models/           # Sequelize models
│   ├── controllers/      # Business logic
│   ├── routes/           # Route definitions
│   └── middleware/       # Auth & upload
└── uploads/              # Uploaded files
    ├── jumbotron/
    ├── berita/
    └── layanan/
```

## Dokumentasi API

### Base URL: `http://localhost:3000/api`

### Autentikasi

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| POST | `/auth/login` | Login admin | Public |
| PUT | `/auth/change-password` | Ganti password | Private |

> Untuk endpoint Private, sertakan header: `Authorization: Bearer <token>`

### Profil

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/profile` | Tampil profil | Public |
| PUT | `/profile` | Update profil (multipart/form-data) | Private |

Fields: `nama_instansi`, `visi`, `misi[]`, `sejarah`, `alamat`, `telepon`, `email`, `website`  
Files: `logo`, `struktur_organisasi_gambar`

### Jumbotron

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/jumbotron` | Semua jumbotron | Public |
| GET | `/jumbotron/:id` | Detail | Public |
| POST | `/jumbotron` | Upload jumbotron | Private |
| PUT | `/jumbotron/:id` | Update | Private |
| DELETE | `/jumbotron/:id` | Hapus | Private |

Filter: `?aktif=true`

### Organisasi

Tipe: `sekretariat` | `aset` | `perbendaharaan` | `akuntansi` | `anggaran`

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/organisasi` | Semua + jabatan | Public |
| GET | `/organisasi/tipe/:tipe` | Per tipe | Public |
| GET | `/organisasi/:id` | Detail | Public |
| POST | `/organisasi` | Tambah | Private |
| PUT | `/organisasi/:id` | Update | Private |
| DELETE | `/organisasi/:id` | Hapus | Private |
| GET | `/organisasi/:id/jabatan` | Jabatan di org | Public |
| POST | `/organisasi/jabatan/create` | Tambah jabatan | Private |
| PUT | `/organisasi/jabatan/:id` | Update jabatan | Private |
| DELETE | `/organisasi/jabatan/:id` | Hapus jabatan | Private |

Jabatan fields: `organisasi_id`, `nama_jabatan`, `nama_pejabat`, `nip`, `tugas_fungsi[]`

### Berita

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/berita` | Semua berita (paginasi) | Public |
| GET | `/berita/slug/:slug` | Berdasarkan slug | Public |
| GET | `/berita/:id` | Detail | Public |
| POST | `/berita` | Tambah berita | Private |
| PUT | `/berita/:id` | Update | Private |
| DELETE | `/berita/:id` | Hapus | Private |

Filter: `?page=1&limit=10&kategori=Kegiatan&aktif=true`  
Upload: `multipart/form-data` dengan field `gambar` (opsional)

### Layanan

Tipe: `penganggaran` | `penatausahaan` | `pelaporan_pertanggungjawaban` | `perencanaan_evaluasi` | `perjanjian_kinerja`

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/layanan` | Semua layanan | Public |
| GET | `/layanan/tipe/:tipe` | Per tipe | Public |
| GET | `/layanan/:id` | Detail | Public |
| POST | `/layanan` | Upload dokumen | Private |
| PUT | `/layanan/:id` | Update | Private |
| DELETE | `/layanan/:id` | Hapus | Private |

Upload: `multipart/form-data` – `file` (pdf/doc/xls/ppt), `tipe`, `judul`, `tahun_apbd`, `deskripsi`  
Filter: `?tahun_apbd=2024`

### Kontak

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| POST | `/kontak` | Kirim pesan | Public |
| GET | `/kontak` | Semua pesan | Private |
| GET | `/kontak/:id` | Detail (auto tandai dibaca) | Private |
| PATCH | `/kontak/:id/status` | Update status | Private |
| DELETE | `/kontak/:id` | Hapus | Private |

Status: `belum_dibaca` | `sudah_dibaca` | `dibalas`

## Konfigurasi .env

```env
PORT=3000
NODE_ENV=development
DB_DIALECT=sqlite
DB_STORAGE=./database.sqlite
JWT_SECRET=your_jwt_secret_key_here
JWT_EXPIRES_IN=7d
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
MAX_FILE_SIZE=5242880
UPLOAD_PATH=./uploads
```

## File Upload

- **Jumbotron & Berita**: jpg, jpeg, png, gif, webp – maks 5MB
- **Layanan**: pdf, doc, docx, xls, xlsx, ppt, pptx – maks 20MB
- Akses file: `/uploads/<folder>/<nama-file>`

## Lisensi

ISC
