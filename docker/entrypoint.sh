#!/bin/sh
set -e

PORT="${PORT:-8080}"

# ── Healthcheck endpoint (plain text, no PHP needed) ──
mkdir -p /var/www/html/public
cat > /var/www/html/public/healthz <<'TXT'
ok
TXT

# ── Set the port Railway assigns ──────────────────────
# Replace the placeholder ${PORT} in the VirtualHost config
sed -i "s/\${PORT}/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Also update ports.conf so Apache listens on the right port
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

echo "✅ Apache starting on port ${PORT}"
echo "   Backend  → /var/www/html/public"
echo "   Frontend → /var/www/html/frontend (aliased to /frontend)"

exec apache2-foreground
