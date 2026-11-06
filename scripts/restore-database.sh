#!/bin/bash
#
# ERP Database Restore Script
# Kullanım: ./restore-database.sh <backup-file>
# Örnek: ./restore-database.sh /var/backups/erp/database/erp_20261106_120000.dump.gz
#
set -euo pipefail

if [ $# -ne 1 ]; then
    echo "Usage: $0 <backup-file>"
    echo "Example: $0 /var/backups/erp/database/erp_20261106_120000.dump.gz"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "${BACKUP_FILE}" ]; then
    echo "❌ Error: ${BACKUP_FILE} not found"
    exit 1
fi

# Config yükle
# shellcheck disable=SC1091
source /etc/erp-backup.env

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚠️  WARNING: This will REPLACE ALL DATA in:"
echo "   Database: ${DB_DATABASE}"
echo "   Server: ${DB_HOST}:${DB_PORT}"
echo "   From: ${BACKUP_FILE}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
read -p "Type 'yes' to continue: " CONFIRM

if [ "${CONFIRM}" != "yes" ]; then
    echo "Cancelled"
    exit 0
fi

export PGPASSWORD="${DB_PASSWORD}"

echo "[$(date)] Restoring..."
gunzip -c "${BACKUP_FILE}" | pg_restore \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USERNAME}" \
    -d "${DB_DATABASE}" \
    --clean \
    --if-exists \
    --no-owner \
    --no-privileges \
    --single-transaction \
    -v 2>&1 | tail -20

echo "[$(date)] ✅ Restore completed"

# Cache temizle
echo "[$(date)] Clearing Laravel cache..."
cd /var/www/erp
php artisan optimize:clear || true

echo "[$(date)] Done"
