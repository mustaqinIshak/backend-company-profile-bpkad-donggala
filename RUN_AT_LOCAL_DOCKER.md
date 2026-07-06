# 1. Pastikan .env terisi lengkap (sudah kamu lakukan)
# DB_HOST=db, CACHE_STORE=file, dst.

# 2. Reset & jalankan ulang (dengan --build jika ada perubahan Dockerfile)
docker compose down -v
docker compose up -d --build

# 3. Pantau log sampai "Setup selesai"
docker compose logs -f app

# 4. Test API
curl http://localhost/api/profile
# phpMyAdmin → http://localhost:8080