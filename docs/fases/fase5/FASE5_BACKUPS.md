# Fase 5 — Backups y Restauración

## Scripts implementados

### `scripts/backup.sh`
- Realiza dump completo de la base de datos con `mysqldump`.
- Usa `--single-transaction` para garantizar consistencia sin bloquear tablas (InnoDB).
- Comprime con `gzip` para reducir espacio.
- Guarda en `storage/backups/` con timestamp (`sgplopypc_YYYYMMDD_HHMMSS.sql.gz`).
- Permisos restrictivos: directorio `700`, archivo `600`.
- Limpieza automática de archivos más antiguos según `BACKUP_RETENTION_DAYS` (default 14 días).
- Carga variables desde `.env`.

### `scripts/restore.sh`
- Restaura un backup `.sql.gz` a la base de datos configurada en `.env`.
- Requiere confirmación explícita escribiendo `RESTAURAR`.
- Usa `gunzip -c` con pipe a `mysql`.

## Variables de entorno relevantes
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sgplopypc
DB_USER=
DB_PASS=
BACKUP_RETENTION_DIAS=14
```

## Procedimiento de restauración documentado

1. Asegurar que el archivo `.env` contenga las credenciales correctas de la base de datos destino.
2. Identificar el backup a restaurar en `storage/backups/`.
3. Ejecutar:
   ```bash
   ./scripts/restore.sh storage/backups/sgplopypc_20260506_120000.sql.gz
   ```
4. Confirmar escribiendo `RESTAURAR` cuando se solicite.
5. Verificar conexión y datos mediante health check o phpMyAdmin.

## Prueba de restauración

- Se validó sintaxis de scripts con `bash -n`.
- Se verificó que los scripts carguen correctamente variables desde `.env`.
- **Nota:** La prueba real de restauración requiere un entorno de base de datos activo. Se recomienda ejecutar `backup.sh` y `restore.sh` en un entorno de staging antes de producción.

## Seguridad
- Los backups **no se versionan** en el repositorio (`.gitignore` cubre `storage/backups/` si se agrega).
- Se recomienda cifrar los backups si contienen datos sensibles de proveedores (ej. `gpg` o almacenamiento en bucket con cifrado en reposo).
- Los permisos de archivo (600) limitan acceso al propietario del proceso.
