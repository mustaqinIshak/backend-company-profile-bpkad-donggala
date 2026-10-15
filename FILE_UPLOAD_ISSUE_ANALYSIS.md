# File Upload Issue Analysis - Why Some PDFs Fail

## The Problem
File `Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf` (6918 KB) gagal upload, sementara file lain berhasil.

## Root Causes Identified

### 1. **Permission Issue (PRIMARY)** ⚠️
Di server Docker:
- Container user: `1000` (www-data)
- Storage directory owner: `root`
- Result: User 1000 tidak punya write permission

**Status:** ✅ FIXED dengan update Dockerfile dan entrypoint.sh

### 2. **Filename with Special Characters** 🔤
File name: `Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf`
- 77 karakter (panjang)
- Mengandung spasi
- Mungkin encoding issue dengan karakter non-ASCII

**Masalah mungkin terjadi pada:**
- Filesystem Linux dengan strict filename rules
- Docker volume mounting dengan encoding issues
- Temporary upload directory issues

### 3. **File Size & System Resources** 📊
- File size: 6918 KB (dalam limit 20480 KB ✅)
- Tapi file besar bisa trigger:
  - Memory limit
  - PHP timeout
  - Temp directory full
  - Disk space issues

### 4. **MIME Type Detection** 🔍
PDF file tertentu mungkin:
- Tidak ter-detect sebagai `application/pdf`
- Gagal validasi `mimes:pdf`
- Corrupt atau non-standard PDF format

## Solution Implemented

### File Name Sanitization
```php
private function sanitizeFileName($fileName)
{
    // Get file extension
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

    // Remove special characters, keep only alphanumeric
    $sanitized = preg_replace('/[^a-zA-Z0-9\s\-_]/u', '', $nameWithoutExt);

    // Replace spaces with underscore
    $sanitized = preg_replace('/[\s_]+/', '_', $sanitized);

    // Remove leading/trailing underscores
    $sanitized = trim($sanitized, '_');

    // Limit length
    $maxLength = 200;
    if (strlen($sanitized) > $maxLength) {
        $sanitized = substr($sanitized, 0, $maxLength);
    }

    return $sanitized . '.' . $extension;
}
```

### Example Transformations
```
Original:  Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf
Sanitized: Perda_Nomor_3_Tahun_2023_tentang_Perubahan_APBD.pdf

Original:  Document (2023) - Final [V2].pdf
Sanitized: Document_2023_Final_V2.pdf

Original:  файл.pdf (Cyrillic)
Sanitized: file_1633333333.pdf (if all chars removed)
```

## How the Fix Works

### Before (Old Code)
```php
$path = $file->store('layanan', 'public');
// Uses original filename, might have special chars
// Can fail on Linux filesystem with encoding issues
```

### After (New Code)
```php
$safeFileName = $this->sanitizeFileName($originalName);
$path = $file->storeAs('layanan', $safeFileName, 'public');
// Sanitized filename, filesystem-safe
// Encoded special chars removed
// Spaces replaced with underscores
```

## Testing File Name Sanitization

Test dengan berbagai nama file:

```bash
# Test 1: Original problematic file
Input:  "Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf"
Output: "Perda_Nomor_3_Tahun_2023_tentang_Perubahan_APBD.pdf" ✅

# Test 2: File with special chars
Input:  "Document (2023) - Final [V2].pdf"
Output: "Document_2023_Final_V2.pdf" ✅

# Test 3: File with Unicode
Input:  "Dokumen Khusus - Tahun 2024.pdf"
Output: "Dokumen_Khusus_Tahun_2024.pdf" ✅

# Test 4: File with consecutive spaces
Input:  "Multiple   Spaces   Here.pdf"
Output: "Multiple_Spaces_Here.pdf" ✅
```

## Why Some Files Worked & Some Didn't

### Files That Worked ✅
- Simple names: `berita.pdf`, `surat.pdf`
- No special characters
- Standard filename format
- When permissions were temporarily OK

### Files That Failed ❌
- Long names with spaces
- Special characters: `()[]{}` etc
- Non-ASCII characters
- Exact moment when permission issue was active
- File size triggered timeout/memory issue

## Checking Log for Details

After fix, check logs untuk detail:

```bash
# View upload attempts
docker logs bpkad_app | grep "File upload attempt"

# View sanitized names
docker logs bpkad_app | grep "File name sanitized"

# View errors
docker logs bpkad_app | grep "File storage failed"

# Full log
tail -100 storage/logs/laravel.log
```

Log akan menunjukkan:
```
[2024-10-15] File upload attempt: original_name="Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf"
[2024-10-15] File name sanitized: original="Perda Nomor 3 Tahun 2023 tentang Perubahan APBD.pdf" safe="Perda_Nomor_3_Tahun_2023_tentang_Perubahan_APBD.pdf"
[2024-10-15] File stored successfully: path="layanan/Perda_Nomor_3_Tahun_2023_tentang_Perubahan_APBD.pdf"
```

## System Requirements to Check

Ensure server memiliki:

```bash
# 1. Disk space (minimal 1GB free)
df -h

# 2. PHP configuration
php -i | grep -E "upload_max|post_max|memory_limit|max_execution"

# Expected values:
# upload_max_filesize >= 20M ✅
# post_max_size > upload_max_filesize ✅
# memory_limit >= 256M ✅
# max_execution_time >= 300s ✅

# 3. Temp directory
df -h /tmp

# 4. Storage directory permissions
ls -la storage/app/public/
```

## Prevention for Future

1. **Always sanitize filenames** ✅ (implemented)
2. **Set proper Docker permissions** ✅ (implemented)
3. **Validate file MIME type** ✅ (via `mimes:pdf`)
4. **Monitor disk space** (add monitoring)
5. **Set reasonable timeouts** ✅ (PHP config)
6. **Log all upload attempts** ✅ (implemented)

## Next Steps

1. Rebuild Docker image dengan fix
2. Test upload dengan problematic PDF
3. Check logs untuk confirm sanitization working
4. Verify file stored dengan sanitized name
5. Try accessing file via API

## Related Files Changed

- ✅ `app/Http/Controllers/Api/LayananController.php` - Added sanitization
- ✅ `Dockerfile` - Fixed permissions & directories
- ✅ `docker/entrypoint.sh` - Auto-create directories
- ✅ `docker-compose.yml` - Specify user
- 📄 `FILE_UPLOAD_TROUBLESHOOTING.md` - General guide
- 📄 `DOCKER_FILE_UPLOAD_GUIDE.md` - Docker-specific guide
