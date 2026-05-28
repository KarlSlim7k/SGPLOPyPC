FROM php:8.2-apache

# Instalar extensiones PHP necesarias en un solo RUN para optimizar capas
# - libzip-dev, ext zip: requerido por PHPWord para generar DOCX
# - libpng/jpeg/freetype + gd: para manipular imágenes en PDF/DOCX (logos, firmas)
# - libxml/dom: requerido por PHPWord
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    default-mysql-client \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql zip gd dom xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer (binario oficial)
RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar MPM y habilitar módulos necesarios en un solo paso
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers alias 2>/dev/null || true

# Configurar Apache para servir desde /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copiar archivos del proyecto
COPY . /var/www/html/

# Instalar dependencias de Composer (Dompdf, PHPWord) sin dev y optimizado
WORKDIR /var/www/html
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-progress; \
    fi

# Copiar configuración de Apache para el VirtualHost
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# Dar permisos y configurar entrypoint en un solo paso
RUN chmod +x /var/www/html/docker/entrypoint.sh /var/www/html/scripts/migrate.sh \
    && ln -sf /var/www/html/docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Healthcheck ultra-ligero para que Railway sepa que el contenedor está listo
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://127.0.0.1:${PORT:-80}/healthz || exit 1

# Puerto por defecto (Railway inyecta PORT via env var)
EXPOSE 80

# Usar el script de entrypoint para iniciar Apache
ENTRYPOINT ["docker-entrypoint.sh"]
