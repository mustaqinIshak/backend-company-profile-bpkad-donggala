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