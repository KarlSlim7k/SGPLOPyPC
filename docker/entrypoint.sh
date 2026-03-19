#!/bin/bash
set -e

# Deshabilitar todos los MPMs y habilitar solo mpm_prefork
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway sets PORT env var — make Apache listen on it (default 80)
# Force 0.0.0.0 binding to ensure Railway proxy can reach the service via IPv4
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen 0.0.0.0:$PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf
else
    sed -i "s/Listen 80/Listen 0.0.0.0:80/" /etc/apache2/ports.conf
fi

# Crear endpoint de healthcheck
mkdir -p /var/www/html/public
echo "ok" > /var/www/html/public/healthz

echo "✅ Apache starting on port ${PORT:-80}"
echo "   Backend  → /var/www/html/public"
echo "   Frontend → /var/www/html/frontend (aliased to /frontend)"

# Ejecutar el comando original de Apache
exec apache2-foreground
