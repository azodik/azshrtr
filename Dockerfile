# syntax=docker/dockerfile:1.7

# -----------------------------------------------------------------------------
# Frontend assets (discarded after copying public/build)
# -----------------------------------------------------------------------------
FROM node:24-bookworm-slim AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY resources ./resources
COPY vite.config.js tsconfig.json ./
COPY public ./public

RUN npm run build \
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# PHP dependencies (production only)
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress

COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources/views ./resources/views
COPY lang ./lang
COPY public ./public
COPY artisan VERSION ./

RUN composer dump-autoload --optimize --no-dev --no-interaction

# -----------------------------------------------------------------------------
# Runtime: Alpine nginx + php-fpm + queue + scheduler
# -----------------------------------------------------------------------------
FROM php:8.5-fpm-alpine AS runtime

ARG AZSHRTR_VERSION=0.0.1
ARG AZSHRTR_BUILD=1
ARG AZSHRTR_COMMIT=unknown

ENV AZSHRTR_VERSION=${AZSHRTR_VERSION} \
    AZSHRTR_BUILD=${AZSHRTR_BUILD} \
    AZSHRTR_COMMIT=${AZSHRTR_COMMIT}

LABEL org.opencontainers.image.title="Azshrtr" \
      org.opencontainers.image.version="${AZSHRTR_VERSION}" \
      org.opencontainers.image.revision="${AZSHRTR_COMMIT}" \
      org.opencontainers.image.description="Azshrtr — SemVer ${AZSHRTR_VERSION}, build ${AZSHRTR_BUILD}"

# PHP 8.5 ships opcache built-in — do not docker-php-ext-install it.
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        linux-headers \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        zip \
        pcntl \
        posix \
        bcmath \
    && pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no"' redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apk del --no-network .build-deps \
    && rm -rf /tmp/pear /tmp/* /var/cache/apk/* \
    && mkdir -p /run/nginx \
    && rm -f /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=vendor --chown=www-data:www-data /app/composer.json /app/composer.lock ./
COPY --from=vendor --chown=www-data:www-data /app/app ./app
COPY --from=vendor --chown=www-data:www-data /app/bootstrap ./bootstrap
COPY --from=vendor --chown=www-data:www-data /app/config ./config
COPY --from=vendor --chown=www-data:www-data /app/database ./database
COPY --from=vendor --chown=www-data:www-data /app/routes ./routes
COPY --from=vendor --chown=www-data:www-data /app/resources/views ./resources/views
COPY --from=vendor --chown=www-data:www-data /app/lang ./lang
COPY --from=vendor --chown=www-data:www-data /app/artisan /app/VERSION ./
COPY --from=vendor --chown=www-data:www-data /app/public ./public
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

COPY docker/nginx.conf /etc/nginx/http.d/azshrtr.conf
COPY docker/php/conf.d/zz-azshrtr.ini /usr/local/etc/php/conf.d/zz-azshrtr.ini
COPY docker/supervisord.conf /etc/supervisor.d/azshrtr.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN printf '%s\n' "{\"version\":\"${AZSHRTR_VERSION}\",\"build\":\"${AZSHRTR_BUILD}\",\"commit\":\"${AZSHRTR_COMMIT}\"}" \
        > /var/www/html/build-info.json \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && find storage bootstrap/cache -type d -exec chmod 775 {} \; \
    && php -r "exit(extension_loaded('pdo_mysql') && extension_loaded('redis') && extension_loaded('pcntl') ? 0 : 1);"

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -fsS http://127.0.0.1/api/v1/health >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
