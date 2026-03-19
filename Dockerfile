FROM php:8.2-apache

# ── System deps + PHP extensions ──────────────────────
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        curl \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo pdo_mysql \
    && a2enmod rewrite headers alias \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# ── Copy application code ────────────────────────────
COPY . /var/www/html

# ── Apache virtual-host (replaces default site) ──────
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# ── Entrypoint ────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=5 \
  CMD curl --fail --silent --show-error http://127.0.0.1:${PORT:-8080}/healthz || exit 1

CMD ["/usr/local/bin/entrypoint.sh"]
