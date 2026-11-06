#!/bin/bash
#
# ERP Database Backup Script
# Kullanım: ./backup-database.sh
# Cron: 0 */6 * * * /var/www/erp/scripts/backup-database.sh >> /var/log/erp-backup.log 2>&1
#
set -euo pipefail

# ─── Config yükle ───────────────────────────────────────────
if [ ! -f /etc/erp-backup.env ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: /etc/erp-backup.env not found" >&2
    exit 1
fi

# shellcheck disable=SC1091
source /etc/erp-backup.env

# ─── Değişkenler ────────────────────────────────────────────
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="erp_${TIMESTAMP}.dump.gz"
FILEPATH="${BACKUP_DIR}/${FILENAME}"
LOG_PREFIX="[$(date '+%Y-%m-%d %H:%M:%S')]"

# ─── Klasör hazırla ─────────────────────────────────────────
mkdir -p "${BACKUP_DIR}"

echo "${LOG_PREFIX} ━━━ Backup started ━━━"
echo "${LOG_PREFIX} Database: ${DB_DATABASE}@${DB_HOST}:${DB_PORT}"
echo "${LOG_PREFIX} Target: ${FILEPATH}"

# ─── pg_dump ────────────────────────────────────────────────
export PGPASSWORD="${DB_PASSWORD}"

echo "${LOG_PREFIX} Running pg_dump..."
if ! pg_dump \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USERNAME}" \
    -d "${DB_DATABASE}" \
    -Fc \
    --no-owner \
    --no-privileges \
    --compress=9 \
    -f "${FILEPATH%.gz}" 2>/tmp/pg_dump_err.log; then
    echo "${LOG_PREFIX} ERROR: pg_dump failed:" >&2
    cat /tmp/pg_dump_err.log >&2
    rm -f "${FILEPATH%.gz}"
    exit 1
fi

# ─── Compress ───────────────────────────────────────────────
echo "${LOG_PREFIX} Compressing..."
gzip -9 "${FILEPATH%.gz}"
SIZE=$(du -h "${FILEPATH}" | cut -f1)
echo "${LOG_PREFIX} Backup created: ${FILEPATH} (${SIZE})"

# ─── Opsiyonel S3 upload ────────────────────────────────────
if [ -n "${S3_BUCKET:-}" ] && command -v aws > /dev/null 2>&1; then
    echo "${LOG_PREFIX} Uploading to s3://${S3_BUCKET}/database/${FILENAME}..."
    if aws s3 cp "${FILEPATH}" "s3://${S3_BUCKET}/database/${FILENAME}" \
        --storage-class STANDARD_IA \
        --region "${AWS_REGION:-eu-central-1}" 2>/tmp/s3_err.log; then
        echo "${LOG_PREFIX} ✅ S3 upload OK"
    else
        echo "${LOG_PREFIX} ⚠️  S3 upload failed:" >&2
        cat /tmp/s3_err.log >&2
        # S3 fail → lokal backup silinmez, devam eder
    fi
else
    echo "${LOG_PREFIX} S3 bucket not configured — skipping upload"
fi

# ─── Retention (eski backup sil) ────────────────────────────
echo "${LOG_PREFIX} Cleaning backups older than ${RETENTION_DAYS} days..."
DELETED=$(find "${BACKUP_DIR}" -name "erp_*.dump.gz" -mtime "+${RETENTION_DAYS}" -print -delete | wc -l)
echo "${LOG_PREFIX} Deleted ${DELETED} old backup(s)"

# ─── Health check ───────────────────────────────────────────
LATEST=$(find "${BACKUP_DIR}" -name "erp_*.dump.gz" -printf '%T@ %p\n' | sort -rn | head -1 | cut -d' ' -f2-)

if [ -z "${LATEST}" ]; then
    echo "${LOG_PREFIX} ❌ ERROR: No backup found after run!" >&2
    exit 1
fi

LATEST_AGE=$(( $(date +%s) - $(stat -c %Y "${LATEST}") ))
if [ ${LATEST_AGE} -gt 90000 ]; then
    echo "${LOG_PREFIX} ❌ ERROR: Latest backup is ${LATEST_AGE}s old (>25h)!" >&2
    exit 1
fi

echo "${LOG_PREFIX} Latest backup age: ${LATEST_AGE}s"
echo "${LOG_PREFIX} ✅ Backup completed successfully"
echo "${LOG_PREFIX} ━━━━━━━━━━━━━━━━━━━━━━━━━"
