#!/bin/bash

# File Upload Issue Diagnostic Script for Armbian
# Run this script at /opt/bpkad-api to diagnose file upload issues

echo "=========================================="
echo "File Upload Diagnostic Script"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get current directory
APP_DIR="/opt/bpkad-api"
STORAGE_DIR="$APP_DIR/storage"
UPLOAD_DIR="$STORAGE_DIR/app/public/layanan"

echo -e "${YELLOW}1. Creating necessary directories...${NC}"
mkdir -p "$STORAGE_DIR/logs"
mkdir -p "$STORAGE_DIR/framework/views"
mkdir -p "$STORAGE_DIR/framework/cache"
mkdir -p "$UPLOAD_DIR"
echo -e "${GREEN}✓ Directories created${NC}"
echo ""

echo -e "${YELLOW}2. Setting proper permissions...${NC}"
chmod -R 775 "$STORAGE_DIR"
chmod -R 755 "$STORAGE_DIR/app/public"

# Get web server user (usually www-data, but could be different)
WEB_USER=$(ps aux | grep -E "nginx|apache|php-fpm" | grep -v grep | awk '{print $1}' | head -1)
if [ -z "$WEB_USER" ]; then
    WEB_USER="www-data"
fi

echo "Web server user detected: $WEB_USER"
sudo chown -R "$WEB_USER:$WEB_USER" "$STORAGE_DIR"
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}3. PHP Configuration:${NC}"
echo "Upload Max Filesize: $(php -r 'echo ini_get("upload_max_filesize");')"
echo "Post Max Size: $(php -r 'echo ini_get("post_max_size");')"
echo "Upload Tmp Dir: $(php -r 'echo ini_get("upload_tmp_dir") ?: "/tmp";')"
echo "Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo ""

echo -e "${YELLOW}4. PHP Extensions:${NC}"
for ext in fileinfo gd curl zip; do
    if php -m | grep -q "$ext"; then
        echo -e "${GREEN}✓${NC} $ext"
    else
        echo -e "${RED}✗${NC} $ext"
    fi
done
echo ""

echo -e "${YELLOW}5. Disk Space:${NC}"
df -h | grep -E "^/|Filesystem"
echo ""

echo -e "${YELLOW}6. Storage Directory Info:${NC}"
echo "Directory: $UPLOAD_DIR"
if [ -d "$UPLOAD_DIR" ]; then
    echo -e "${GREEN}✓ Directory exists${NC}"
    echo "Permissions: $(stat -c '%A (%a)' "$UPLOAD_DIR")"
    echo "Owner: $(stat -c '%U:%G' "$UPLOAD_DIR")"
    echo "Is Writable: $([ -w "$UPLOAD_DIR" ] && echo -e "${GREEN}Yes${NC}" || echo -e "${RED}No${NC}")"
    FILE_COUNT=$(find "$UPLOAD_DIR" -type f | wc -l)
    echo "Files Count: $FILE_COUNT"
    if [ "$FILE_COUNT" -gt 0 ]; then
        TOTAL_SIZE=$(du -sh "$UPLOAD_DIR" | cut -f1)
        echo "Total Size: $TOTAL_SIZE"
    fi
else
    echo -e "${RED}✗ Directory does not exist${NC}"
fi
echo ""

echo -e "${YELLOW}7. Web Server Configuration:${NC}"
if command -v nginx &> /dev/null; then
    echo "Nginx installed"
    NGINX_BODY_SIZE=$(nginx -T 2>/dev/null | grep "client_max_body_size" | head -1)
    if [ -z "$NGINX_BODY_SIZE" ]; then
        echo -e "${YELLOW}Warning: client_max_body_size not found or set to default (1m)${NC}"
    else
        echo "Nginx client_max_body_size: $NGINX_BODY_SIZE"
    fi
fi

if command -v apache2ctl &> /dev/null; then
    echo "Apache2 installed"
fi
echo ""

echo -e "${YELLOW}8. Recent Log Entries:${NC}"
if [ -f "$STORAGE_DIR/logs/laravel.log" ]; then
    echo -e "${GREEN}✓ Log file exists${NC}"
    echo "Recent errors:"
    tail -20 "$STORAGE_DIR/logs/laravel.log" | grep -i "error\|exception" || echo "No recent errors found"
else
    echo -e "${YELLOW}Log file not created yet (will be created after first request)${NC}"
fi
echo ""

echo -e "${YELLOW}9. Recommendations:${NC}"
UPLOAD_SIZE=$(php -r 'echo ini_get("upload_max_filesize");' | grep -oE '[0-9]+')
if [ "$UPLOAD_SIZE" -lt 20 ]; then
    echo -e "${RED}✗ Increase upload_max_filesize to at least 20M in php.ini${NC}"
else
    echo -e "${GREEN}✓${NC} upload_max_filesize is adequate"
fi

if [ ! -w "$UPLOAD_DIR" ]; then
    echo -e "${RED}✗ Fix storage directory permissions${NC}"
else
    echo -e "${GREEN}✓${NC} Storage directory is writable"
fi

FREE_SPACE=$(df "$STORAGE_DIR" | tail -1 | awk '{print $4}')
if [ "$FREE_SPACE" -lt 1000000 ]; then
    echo -e "${RED}✗ Low disk space (less than 1GB free)${NC}"
else
    echo -e "${GREEN}✓${NC} Sufficient disk space available"
fi
echo ""

echo -e "${GREEN}=========================================="
echo "Diagnostic complete!"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Try uploading a file"
echo "2. Check the log file: tail -50 $STORAGE_DIR/logs/laravel.log"
echo "3. If still failing, run: tail -50 $STORAGE_DIR/logs/laravel.log | grep -i error"
