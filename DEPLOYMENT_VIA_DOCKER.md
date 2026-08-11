# 1. Install Docker di Armbian (jika belum)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER ; newgrp docker
sudo apt install -y docker-compose-plugin

# 2. Clone repo ke server
git clone <url-repo> /opt/bpkad-api
cd /opt/bpkad-api

# 3. Buat .env dari template production
cp .env.docker .env
nano .env
# → Isi: APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD, ADMIN_PASSWORD
# → Isi: SANCTUM_STATEFUL_DOMAINS=domain atau IP frontend

# 4. Jalankan dengan konfigurasi production
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 5. Pantau
docker compose logs -f app

---

# 6. Troubleshooting & Penyelesaian Masalah (Khusus Cloudflare / Production)

Jika Anda menemui error setelah aplikasi berjalan, berikut adalah beberapa masalah umum dan solusinya yang barangkali terjadi:

### A. Error 500: `No application encryption key has been specified`
**Penyebab:** Docker membaca file `.env` yang kosong pada start-up, sehingga cache Laravel telanjur merekam kekosongan kunci tersebut meski skrip sudah men-generate yang baru.
**Solusi:** Setel ulang cache konfigurasi langsung ke dalam container:
```bash
docker exec -it bpkad_app php artisan config:clear
docker exec -it bpkad_app php artisan config:cache
```
*(Atau Anda bisa mematikan semua container dengan perintah `down` penuh, dan melakukan `up -d` agar membaca ulang file `.env`).*

### B. Error 405 Method Not Allowed (POST menjadi GET) & 429 Too Many Requests
**Penyebab:** Terjadi perulangan redirect (Looping 301) antara Cloudflare dan Nginx akibat ketidaksesuaian skema HTTP dan HTTPS. Putaran berulang ini secara langsung menghabiskan batas *Rate Limiting* / Throttle dari Laravel, menyebabkan error 429.
**Solusi Langkah Demi Langkah:**
1. **Perbaiki Mode SSL Cloudflare:** Pada Dasbor Cloudflare domain Anda, buka tab **SSL/TLS** -> **Overview**, pastikan enkripsinya diatur ke mode **Full** atau **Full (strict)**, jangan *Flexible*.
2. **Kompilasi Ulang Kepercayaan Proxy (Di Source Code Laravel):** 
   - Pada file `bootstrap/app.php`, pastikan terdapat `$middleware->trustProxies(at: '*');`.
   - Pada file `app/Providers/AppServiceProvider.php`, pastikan terdapat pemaksaan *Scheme HTTPS*.
     ```php
     use Illuminate\Support\Facades\URL;

     if (env('APP_ENV') === 'production') {
         URL::forceScheme('https');
     }
     ```
3. **Segarkan Server (Wajib jika Kode Dirubah):** Setelah melakukan `git pull` dari repositori untuk fitur di atas, bersihkan cache agar perubahan dimuat ke sistem:
   ```bash
   docker exec -it bpkad_app php artisan config:clear
   docker exec -it bpkad_app php artisan route:clear
   docker exec -it bpkad_app php artisan cache:clear
   ```