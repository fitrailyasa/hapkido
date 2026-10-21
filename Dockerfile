# syntax=docker/dockerfile:1

###############################################################################
# Hapkido - Laravel 11 application image
#
# The stack runs completely offline:
#   * every front-end dependency is compiled into public/build by Vite
#   * every third party asset is committed under public/vendor and public/dist
#   * the database is a SQLite file that lives inside a named docker volume
#
# Build:  docker compose build
# Run:    docker compose up -d
###############################################################################

# ----------------------------------------------------------------------------
# Stage 1 - PHP dependencies (Composer)
# ----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Install the PHP dependencies first so this layer is reused between builds.
# The gd and zip extensions are not available inside the composer image, they
# are compiled into the runtime image further down.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --ignore-platform-req=ext-gd \
        --ignore-platform-req=ext-zip

# The application code itself.
COPY . .

# Build the optimized autoloader and register the service providers. Package
# discovery is retried at runtime if it cannot run inside the build.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && (php artisan package:discover --ansi --no-interaction \
        || echo "WARNING: package discovery failed, it is retried at runtime")

# ----------------------------------------------------------------------------
# Stage 2 - front-end assets (Vite + Tailwind)
# ----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

# Install the npm dependencies first so this layer is reused between builds.
COPY package.json package-lock.json ./
RUN if [ -f package-lock.json ]; then \
        npm ci --no-audit --no-fund || npm install --no-audit --no-fund; \
    else \
        npm install --no-audit --no-fund; \
    fi

# Sources that are required by the asset build.
COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

# tailwind.config.js also scans the Laravel pagination views that are shipped
# inside the vendor directory, therefore they are taken from the vendor stage.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

# Vite writes the compiled assets to public/build (laravel-vite-plugin default).
RUN npm run build

# ----------------------------------------------------------------------------
# Stage 3 - runtime image (PHP-FPM)
# ----------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Runtime libraries that are required by the PHP extensions and the app.
RUN apk add --no-cache \
        icu-libs \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        zlib \
        su-exec \
        tzdata

# Compile the PHP extensions the application needs:
#   gd      - required by phpoffice/phpspreadsheet (maatwebsite/excel)
#   intl    - required by the Laravel framework for locale handling
#   zip     - required by phpoffice/phpspreadsheet (maatwebsite/excel)
#   bcmath  - used for the spreadsheet calculations
#   pcntl   - used by the queue worker and the process supervision
#   exif    - used to read the metadata of the uploaded photos
# pdo_sqlite, sqlite3, mbstring and opcache are already part of the official
# image and are only verified at the end of this step.
RUN set -eu; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        zlib-dev; \
    install_extension() { \
        ext="$1"; \
        shift; \
        if php -m | grep -qix "$ext"; then \
            echo "==> $ext is already available, skipping"; \
        else \
            echo "==> building $ext"; \
            docker-php-ext-configure "$ext" "$@"; \
            docker-php-ext-install -j"$(nproc)" "$ext"; \
        fi; \
    }; \
    install_extension gd --enable-gd --with-freetype --with-jpeg; \
    install_extension intl --enable-intl; \
    install_extension zip --with-zip; \
    install_extension bcmath --enable-bcmath; \
    install_extension pcntl --enable-pcntl; \
    install_extension exif --enable-exif; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/tmp/*; \
    php -m | grep -qx 'pdo_sqlite'; \
    php -m | grep -qx 'sqlite3'; \
    php -m | grep -qx 'mbstring'; \
    php -m | grep -q 'Zend OPcache'

# Unprivileged user that owns the application files and runs the PHP workers.
RUN set -eux; \
    addgroup -g 1000 -S app; \
    adduser -u 1000 -S -G app -h /var/www/html app

WORKDIR /var/www/html

# Application code, PHP dependencies and the compiled front-end assets.
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Writable directories and the correct ownership for them.
RUN set -eux; \
    mkdir -p \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /srv/public; \
    chown -R app:app /var/www/html /srv/public; \
    chmod -R ug+rwX \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
        /var/www/html/storage; \
    chmod 755 /var/www/html

# Keep a pristine copy of the migrations. The database directory is backed by
# a named volume, so the migrations have to be refreshed when the image is
# rebuilt (see docker/php/entrypoint.sh).
RUN set -eux; \
    mkdir -p /opt/app/database; \
    for dir in migrations seeders factories; do \
        if [ -d "/var/www/html/database/$dir" ]; then \
            cp -a "/var/www/html/database/$dir" /opt/app/database/; \
        fi; \
    done

# The PHP-FPM workers always run as the unprivileged "app" user, the entry
# point drops the privileges before starting the master process.
RUN set -eux; \
    sed -ri 's/^(user|group) = www-data/#\1 = www-data/' /usr/local/etc/php-fpm.d/www.conf; \
    printf '%s\n' \
        'memory_limit = 512M' \
        'upload_max_filesize = 20M' \
        'post_max_size = 20M' \
        'max_execution_time = 300' \
        'max_input_vars = 5000' \
        > /usr/local/etc/php/conf.d/zz-app.ini; \
    printf '%s\n' \
        'opcache.enable = 1' \
        'opcache.enable_cli = 0' \
        'opcache.memory_consumption = 128' \
        'opcache.max_accelerated_files = 20000' \
        'opcache.validate_timestamps = 0' \
        > /usr/local/etc/php/conf.d/zz-opcache.ini

# Helper scripts.
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/php/healthcheck.sh /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/healthcheck

# The application is only reachable through the nginx container.
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
