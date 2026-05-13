# Panduan Testing Lokal – Setelah Implementasi Security

Dokumen ini menjelaskan langkah-langkah untuk menguji fitur keamanan (httpOnly Cookie, Rate Limiting, Security Headers) di lingkungan lokal.

---

## Prasyarat

- PHP 8.2+ sudah terinstall
- Composer sudah terinstall
- Backend sudah berjalan (`php artisan serve`)
- Frontend dashboard-admin sudah diupdate (lihat **Langkah 3**)

---

## Langkah 1 – Pastikan Konfigurasi `.env` Lokal

Buka file `.env` di root project backend dan pastikan:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Sesuaikan dengan port frontend Anda
FRONTEND_URLS=http://localhost:3000,http://localhost:5173
```

> **Penting:** `APP_ENV=local` memastikan HTTPS redirect dan HSTS **tidak aktif** di lokal.

---

## Langkah 2 – Clear Cache Konfigurasi

Jalankan perintah berikut agar perubahan konfigurasi terbaca ulang:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

Lalu jalankan server lokal:

```bash
php artisan serve
```

---

## Langkah 3 – Update Frontend Dashboard-Admin

Karena token kini disimpan di httpOnly cookie (tidak lagi di response body), frontend perlu diperbarui.

### 3a. Update `axios.ts`

Tambahkan `withCredentials: true` pada instance Axios agar browser mengirimkan cookie di setiap request:

```ts
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true, // wajib — agar cookie auth_token dikirim otomatis
});
```

### 3b. Update `authStore.ts`

Token tidak lagi ada di response login. Hapus semua referensi ke `token` dari state dan `setAuth`. Cukup simpan data `admin`:

```ts
// Sebelum (hapus ini):
// setAuth(data.token, data.admin)
// localStorage.setItem('auth_token', token)

// Sesudah — hanya simpan data admin:
setAuth(data.admin)
```

### 3c. Update fungsi `setAuth` di store

```ts
// Sebelum:
setAuth: (token: string, admin: Admin) => { ... }

// Sesudah:
setAuth: (admin: Admin) => { ... }
```

---

## Langkah 4 – Test Login

1. Buka aplikasi frontend di browser
2. Login dengan email dan password valid
3. Buka **DevTools** (F12) → tab **Application** → **Cookies** → pilih `http://localhost:8000`
4. Pastikan cookie `auth_token` muncul dengan atribut:

| Atribut | Nilai yang Diharapkan |
|---|---|
| `HttpOnly` | ✓ (centang) |
| `Secure` | ✗ (kosong — normal di lokal HTTP) |
| `SameSite` | `Strict` |

5. Pastikan **tab Network** pada response login **tidak menampilkan field `token`** di JSON body — hanya ada `admin`.

---

## Langkah 5 – Test Autentikasi via Cookie

1. Setelah login, buka tab **Network** di DevTools
2. Lakukan request ke endpoint protected, misal klik menu apapun di dashboard
3. Periksa request header — harus ada:

```
Cookie: auth_token=<nilai_token>
```

4. Response harus `200 OK` tanpa perlu mengirim `Authorization` header secara manual

---

## Langkah 6 – Test Rate Limiting Login

Lakukan percobaan login dengan **password salah sebanyak 6 kali berturut-turut**:

- Percobaan ke-1 sampai ke-5: response `422 Unprocessable Content` (email/password salah)
- Percobaan ke-6: response `429 Too Many Requests`

```json
{
    "message": "Too Many Attempts."
}
```

> Setelah kena rate limit, tunggu **1 menit** sebelum bisa mencoba lagi.

---

## Langkah 7 – Test Security Headers

Buka DevTools → tab **Network** → klik salah satu request API → tab **Response Headers**.

Header yang harus muncul:

| Header | Nilai |
|---|---|
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), ...` |
| `Content-Security-Policy` | `default-src 'none'; frame-ancestors 'none'` |

> **Catatan:** `Strict-Transport-Security` (HSTS) **tidak akan muncul di lokal** — ini normal. Header tersebut hanya dikirim saat `APP_ENV=production`.

---

## Langkah 8 – Test Logout

1. Klik tombol logout di dashboard
2. Buka DevTools → **Application** → **Cookies**
3. Cookie `auth_token` harus **hilang** setelah logout
4. Coba akses endpoint protected secara manual (misal via Postman tanpa cookie) → harus mendapat `401 Unauthenticated`

---

## Langkah 9 – Test di Postman (Opsional)

Untuk menguji API secara langsung tanpa browser:

1. Buka Postman → **Settings** → aktifkan **"Automatically follow redirects"** dan **"Send cookies"**
2. `POST http://localhost:8000/api/auth/login` dengan body:
   ```json
   { "email": "admin@example.com", "password": "password123" }
   ```
3. Setelah login berhasil, cek tab **Cookies** di Postman — `auth_token` harus tersimpan
4. Request berikutnya ke endpoint protected akan otomatis menyertakan cookie tersebut

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| `401` setelah login padahal cookie ada | Pastikan `withCredentials: true` sudah ditambahkan di `axios.ts` frontend |
| Cookie tidak muncul di browser | Cek CORS — pastikan `FRONTEND_URLS` di `.env` sesuai dengan port frontend yang digunakan |
| `419 CSRF token mismatch` | Panggil `GET /sanctum/csrf-cookie` sebelum login, atau pastikan `EnsureFrontendRequestsAreStateful` sudah terdaftar |
| Rate limit terlalu cepat saat development | Tambahkan `CACHE_DRIVER=array` di `.env` agar rate limit tidak persisten antar request test |
