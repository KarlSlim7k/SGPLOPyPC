#!/bin/bash
set -euo pipefail

# Script de restauración de base de datos SGPLOPyPC
# Uso: ./scripts/restore.sh <archivo.sql.gz>
# ADVERTENCIA: Sobrescribe datos existentes. Usar con precaución.

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

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Error: DB_USER y DB_PASS deben estar configurados."
    exit 1
fi

if [ $# -lt 1 ]; then
    echo "Uso: $0 <archivo.sql.gz>"
    echo "Restaura un backup comprimido en la base de datos configurada."
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: No se encontró el archivo ${BACKUP_FILE}"
    exit 1
fi

echo "============================================================="
echo " ADVERTENCIA: ESTA OPERACIÓN SOBREESCRIBIRÁ LA BASE DE DATOS"
echo " Base de destino: ${DB_NAME} @ ${DB_HOST}:${DB_PORT}"
echo " Archivo de origen: ${BACKUP_FILE}"
echo "============================================================="
read -r -p "¿Deseas continuar? (escribe RESTAURAR para confirmar): " CONFIRM

if [ "$CONFIRM" != "RESTAURAR" ]; then
    echo "Operación cancelada."
    exit 0
fi

echo "Restaurando base de datos..."

gunzip -c "$BACKUP_FILE" | mysql \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    "$DB_NAME"

echo "Restauración completada exitosamente."
