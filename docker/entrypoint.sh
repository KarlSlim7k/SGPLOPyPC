#!/bin/sh
set -e

PORT="${PORT:-8080}"
DOCROOT="${APACHE_DOCUMENT_ROOT:-/var/www/html/public}"

mkdir -p "${DOCROOT}"

# Static healthcheck endpoint served directly by Apache.
# This avoids relying on PHP execution for platform liveness checks.
cat > "${DOCROOT}/healthz" <<'TXT'
ok
TXT

if [ ! -f "${DOCROOT}/index.php" ]; then
  cat > "${DOCROOT}/index.php" <<'PHP'
<?php
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'service' => 'sgplopypc-backend',
    'message' => 'Backend running on Railway'
]);
PHP
fi

sed -ri "s!/var/www/html!${DOCROOT}!g" /etc/apache2/sites-available/000-default.conf
sed -ri "s!/var/www/!${DOCROOT}!g" /etc/apache2/apache2.conf
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
