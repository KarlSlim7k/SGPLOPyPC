#!/bin/bash
set -euo pipefail

# Script de respaldo de base de datos SGPLOPyPC
# Uso: ./scripts/backup.sh
# Requiere: mysqldump, gzip, variables de entorno cargadas o archivo .env

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="${PROJECT_DIR}/.env"

if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck source=/dev/null
    source "$ENV_FILE"
    set +a
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-sgplopypc}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
BACKUP_DIR="${PROJECT_DIR}/storage/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sql.gz"

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Error: DB_USER y DB_PASS deben estar configurados."
    exit 1
fi

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

echo "Iniciando respaldo de ${DB_NAME}..."
mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --hex-blob \
    --skip-lock-tables \
    "$DB_NAME" | gzip > "$BACKUP_FILE"

chmod 600 "$BACKUP_FILE"

# Limpiar backups antiguos según retención
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +"$RETENTION_DAYS" -delete

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "Respaldo completado: ${BACKUP_FILE} (${BACKUP_SIZE})"
echo "Retención configurada: ${RETENTION_DAYS} días"
