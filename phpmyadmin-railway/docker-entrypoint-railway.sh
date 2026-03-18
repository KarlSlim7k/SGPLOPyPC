#!/bin/sh
set -e

# Ensure a single Apache MPM is active.
a2dismod mpm_event >/dev/null 2>&1 || true
a2dismod mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# Let the official entrypoint create config.secret.inc.php and wire Apache port.
export APACHE_PORT="${PORT:-80}"

exec /docker-entrypoint.sh apache2-foreground
