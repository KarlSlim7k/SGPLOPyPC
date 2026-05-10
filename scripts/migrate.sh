#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_DIR"

DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "Migraciones omitidas: variables DB_* incompletas."
  exit 0
fi

if ! command -v php >/dev/null 2>&1; then
  echo "Error: php no esta disponible."
  exit 1
fi

MAX_ATTEMPTS="${MIGRATION_DB_MAX_ATTEMPTS:-30}"
RETRY_SECONDS="${MIGRATION_DB_RETRY_SECONDS:-3}"

echo "Ejecutando migraciones de esquema..."
for attempt in $(seq 1 "$MAX_ATTEMPTS"); do
  if php -r 'require "config/database.php"; getDbConnection()->query("SELECT 1");' >/dev/null 2>&1; then
    break
  fi
  if [ "$attempt" = "$MAX_ATTEMPTS" ]; then
    echo "Error: no se pudo conectar a MySQL para migraciones."
    exit 1
  fi
  echo "MySQL no disponible aun; reintento ${attempt}/${MAX_ATTEMPTS}..."
  sleep "$RETRY_SECONDS"
done

php "${PROJECT_DIR}/scripts/migrate.php"
