# Panduan Penyimpanan Docker di SSD Eksternal & Backup Rutin

Dokumen ini berisi panduan teknis langkah demi langkah untuk:
1. Memindahkan pusat data Docker ke SSD/HDD Eksternal (agar eMMC / memori internal server yang terbatas ukurannya tidak cepat penuh).
2. Membuat sistem pencadangan (backup) database otomatis setiap minggu.

---

## 1. Memindahkan Penyimpanan Docker ke SSD/HDD

Langkah ini diperlukan pada server berjenis *Mini PC* atau *STB Armbian* yang memiliki memori internal kecil, sedangkan seluruh log container, database, dan unggahan foto (Named Volumes Docker) by default disimpan di bagian dasar Linux `/var/lib/docker`.

### A. Persiapan dan Mounting SSD
1. **Cek lokasi / nama drive SSD Anda:**
   ```bash
   lsblk
   ```
   *Cari partisi dengan ukuran fisik paling besar. Misal: SSD Anda terbaca sebagai `/dev/sda1`.*

2. **Ketahi UUID partisi tersebut:**
   ```bash
   sudo blkid /dev/sda1
   ```
   *Salin/Catat kode UUID yang muncul (tanpa tanda kutip ganda).*

3. **Buat direktori tujuan (mount point):**
   ```bash
   sudo mkdir -p /mnt/ssd
   ```

4. **Daftarkan ke `/etc/fstab`:**  
   Langkah ini wajib agar SSD otomatis di-mount saat server mati listrik atau direstart.
   ```bash
   sudo nano /etc/fstab
   ```
   Tambahkan di baris **paling bawah**:
   ```text
   UUID=masukkan-kode-uuid-disini    /mnt/ssd    ext4    defaults,noatime    0   2
   ```

5. **Terapkan konfigurasi mount:**
   ```bash
   sudo mount -a
   sudo systemctl daemon-reload
   df -h /mnt/ssd
   ```

### B. Memigrasikan Data Inti Docker
1. **Matikan layanan Docker sepenuhnya:**
   ```bash
   sudo systemctl stop docker
   sudo systemctl stop docker.socket
   ```

2. **Buat folder khusus untuk Docker di SSD dan salin data lama:**
   ```bash
   sudo mkdir -p /mnt/ssd/docker-data
   sudo rsync -aqxP /var/lib/docker/ /mnt/ssd/docker-data/
   ```

3. **Beri tahu Docker lokasi barunya (Konfigurasi Daemon):**
   ```bash
   sudo nano /etc/docker/daemon.json
   ```
   Isi dengan format JSON berikut:
   ```json
   {
       "data-root": "/mnt/ssd/docker-data"
   }
   ```

4. **Nyalakan kembali Docker & Verifikasi:**
   ```bash
   sudo systemctl start docker
   docker info | grep "Docker Root Dir"
   ```
   *(Jika sukses, outputnya akan langsung menunjuk ke `/mnt/ssd/docker-data`)*.  
   Anda dapat menghapus data Docker yang lama di memori internal dengan: `sudo rm -rf /var/lib/docker`.

---

## 2. Setup Backup Otomatis Rutin (Mingguan)

Kita akan membuat *Shell Script* yang mengekstrak data dari database MariaDB menjadi file `.sql.gz` dan menjadwalkan eksekusinya menggunakan `crontab` setiap hari Minggu.

### A. Buat Direktori Penampung Backup di SSD
```bash
sudo mkdir -p /mnt/ssd/backups
```

### B. Buat Skrip Backup (`backup.sh`)
Buat sebuah file eksekutor di folder asal server Anda (atau di manapun yang rapi):
```bash
sudo nano /opt/bpkad-api/backup.sh
```

Isi dengan skrip berikut:
```bash
#!/bin/bash

# --- Konfigurasi ---
BACKUP_DIR="/mnt/ssd/backups"
DATE=$(date +"%Y-%m-%d_%H-%M")
CONTAINER_DB="bpkad_db"     # Nama container database dari docker-compose
DB_USER="bpkad_user"        # Samakan dengan DB_USERNAME di file .env
DB_PASS="password_db_anda"  # Samakan dengan DB_PASSWORD di file .env
DB_NAME="bpkad_donggala"    # Samakan dengan DB_DATABASE di file .env

echo ">>> Memulai tugas backup pada $DATE..."

# 1. Mengekspor (Dump) Database langsung dari Container ke file .sql sementara
docker exec $CONTAINER_DB mariadb-dump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# 2. Kompresi file SQL menjadi format .tar.gz (untuk menghemat ruang SSD)
tar -czvf $BACKUP_DIR/db_backup_$DATE.tar.gz -C $BACKUP_DIR db_backup_$DATE.sql

# 3. Menghapus file .sql yang belum terkompresi
rm $BACKUP_DIR/db_backup_$DATE.sql

# 4. (Opsional) Auto-hapus file backup yang lebih lama dari 30 hari agar SSD tak kepenuhan
find $BACKUP_DIR -type f -name "*.tar.gz" -mtime +30 -exec rm {} \;

echo ">>> Backup selesai disimpan di $BACKUP_DIR"
```

*Jangan lupa mengganti variabel DB_PASS dengan password yang asli yang ada di dalam `.env` server Anda.*  
*Simpan dan keluar (Ctrl+O, Enter, Ctrl+X).*

### C. Berikan Hak Akses Eksekusi Skrip
Agar skrip bisa dijalankan secara otomatis oleh sistem, ia butuh izin (permission):
```bash
sudo chmod +x /opt/bpkad-api/backup.sh
```

*(Anda bisa mencoba mengetes skrip ini secara manual dengan mengetik:* `./backup.sh` *di terminal).*

### D. Buat Penjadwalan Otomatis (Cron Job)
Kita akan menjadwalkan agar skrip `backup.sh` berjalan sendiri saat semua orang sedang tidur (Setiap hari **Minggu, Jam 02:00 Pagi**).

1. Buka editor Cron:
   ```bash
   sudo crontab -e
   ```
   *(Pilih editor nano jika ditanya).*

2. Tambahkan baris sakti ini di posisi paling bawah:
   ```cron
   0 2 * * 0 /opt/bpkad-api/backup.sh >> /mnt/ssd/backups/backup.log 2>&1
   ```
   Keterangan Kode Cron `0 2 * * 0`:
   - `0`  = Menit ke-0
   - `2`  = Jam 02 pagi
   - `*`  = Setiap tanggal
   - `*`  = Setiap bulan
   - `0`  = Hari Minggu
   - `>> /mnt/ssd/backups/backup.log` = Mencatat riwayat sukses/gagal di dalam file teks.

Simpan editor crontab tersebut.  
🎉 **Selesai!**  Server API BPKAD Donggala Anda kini sudah kuat menampung ratusan ribu data berkat Docker di dalam SSD, serta anti malapetaka berkat sistem Snapshot Backup otomatis setiap Minggu Pagi.
