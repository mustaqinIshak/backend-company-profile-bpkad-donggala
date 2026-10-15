# Docker File Upload Troubleshooting Guide

## Problem: "The file dokumen failed to upload" in Docker

Masalah file upload gagal di Docker biasanya disebabkan oleh:
1. Permission mismatch antara host dan container
2. Storage directory tidak ter-create dengan benar
3. Named volume tidak di-mount dengan proper user

## Solution

### 1. Update Dockerfile
Dockerfile sudah di-update untuk:
- ✅ Create semua storage directories
- ✅ Set proper permissions (775 untuk storage, 755 untuk public)
- ✅ Set user 1000:1000 (www-data)

### 2. Update docker-compose.yml
docker-compose.yml sudah di-update untuk:
- ✅ Specify user 1000:1000 di app service
- ✅ Proper named volume mounting

### 3. Update Entrypoint Script
`docker/entrypoint.sh` sudah di-update untuk:
- ✅ Create directories saat container startup
- ✅ Set permissions automatically
- ✅ Handle permission fixes

## Deployment Steps

### Fresh Deployment
```bash
cd /opt/bpkad-api

# Rebuild dengan image baru
docker-compose down
docker-compose up -d --build

# Verify
docker logs bpkad_app
```

### Existing Deployment (Fix for Current Issue)
```bash
cd /opt/bpkad-api

# Stop containers
docker-compose down

# Optional: Remove old volume untuk fresh start
docker volume rm bpkad_storage_public

# Rebuild with new config
docker-compose up -d --build

# Verify containers
docker ps

# Check app logs
docker logs -f bpkad_app

# Verify permissions inside container
docker exec bpkad_app ls -la /var/www/html/storage/app/public/
docker exec bpkad_app ls -la /var/www/html/storage/logs/
```

## Verification Commands

```bash
# Check if upload directory exists in container
docker exec bpkad_app test -d /var/www/html/storage/app/public/layanan && echo "✓ Directory exists" || echo "✗ Directory not found"

# Check directory permissions
docker exec bpkad_app stat /var/www/html/storage/app/public/layanan

# Check file ownership
docker exec bpkad_app ls -la /var/www/html/storage/app/public/

# Test file upload via artisan
docker exec bpkad_app php artisan debug:file-upload --check-logs

# View recent logs
docker logs bpkad_app | grep -i "upload\|file\|dokumen"
```

## If Issue Persists

### 1. Check Volume Ownership
```bash
# On host system
docker volume inspect bpkad_storage_public
ls -la /var/lib/docker/volumes/bpkad_storage_public/_data/
```

### 2. Manually Fix Permissions in Container
```bash
docker exec -u root bpkad_app chown -R www-data:www-data /var/www/html/storage
docker exec -u root bpkad_app chmod -R 775 /var/www/html/storage
docker exec -u root bpkad_app chmod -R 755 /var/www/html/storage/app/public
```

### 3. Recreate Named Volume
```bash
cd /opt/bpkad-api

# Stop containers
docker-compose down

# Remove volume
docker volume rm bpkad_storage_public

# Restart with fresh volume
docker-compose up -d --build

# Wait for entrypoint to create directories
sleep 5
docker logs bpkad_app
```

## Docker Compose Configuration Highlights

```yaml
app:
  # Ensure PHP-FPM runs as user 1000 (www-data)
  user: "1000:1000"
  
  volumes:
    # Bind current directory
    - .:/var/www/html
    # Named volume for persistent storage
    - storage_public:/var/www/html/storage/app/public
```

## Entrypoint Script Highlights

Entrypoint script sekarang:
1. Generate APP_KEY
2. Run migrations
3. Run seeders
4. **Create all storage directories** ← New
5. **Set proper permissions** ← New
6. Create storage symlink
7. Cache optimization
8. Start PHP-FPM

## Key Points

- ✅ User 1000:1000 di container matches www-data di Dockerfile
- ✅ Permissions 775 untuk storage (read/write/execute)
- ✅ Permissions 755 untuk storage/app/public (read/execute only)
- ✅ Directories auto-created di entrypoint
- ✅ Named volume untuk persistent storage

## Testing Upload

```bash
# Test dengan curl
curl -X POST http://localhost/api/layanans \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "tipe=sekretariat" \
  -F "judul=Test" \
  -F "tahun_apbd=2024" \
  -F "file_dokumen=@/path/to/file.pdf"

# Check if file was stored
docker exec bpkad_app ls -la /var/www/html/storage/app/public/layanan/
```

## Performance Tips

1. **Use bind mounts for development**, named volumes for production
2. **Consider adding cache volume** untuk `storage/framework/cache/`
3. **Monitor disk space** di host system
4. **Backup named volumes** secara regular

## Troubleshooting Checklist

- [ ] Docker image rebuilt dengan latest Dockerfile
- [ ] docker-compose.yml di-update dengan user specification
- [ ] Containers di-restart setelah changes
- [ ] Entrypoint script di-execute (check logs)
- [ ] Storage directory ada di container
- [ ] Permissions benar (775 untuk storage)
- [ ] User ownership benar (www-data:www-data)
- [ ] Named volume ter-create
- [ ] Upload file test berhasil
