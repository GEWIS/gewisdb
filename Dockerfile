#syntax=docker/dockerfile:1
ARG PHP_VERSION=8.5
ARG FRANKENPHP_VERSION=1.12

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS frankenphp_upstream

# GEWISDB Base Image
FROM frankenphp_upstream AS gewisdb_web_base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app
VOLUME /app/var/

# `pcntl` is required for Messenger to shut down gracefully.
RUN <<-EOF
    apt-get update
    apt-get install -y --no-install-recommends \
        ca-certificates \
        file \
        fonts-dejavu-core \
        git \
        libicu-dev \
        libldap-common
    install-php-extensions \
        @composer \
        apcu \
        calendar \
        intl \
        ldap \
        opcache \
        pcntl \
        pdo_pgsql \
        zip
    rm -rf /var/lib/apt/lists/*
EOF

# Arch-specific binaries, selected on the BuildKit target so arm64 hosts do not fall back to emulation.
ARG TARGETARCH
ARG SASS_VERSION=1.102.0
ARG SWC_VERSION=v1.15.47

RUN <<-EOF
    case "$TARGETARCH" in
        amd64) SASS_ARCH=linux-x64; SWC_ARCH=linux-x64-gnu ;;
        arm64) SASS_ARCH=linux-arm64; SWC_ARCH=linux-arm64-gnu ;;
        *) echo "Unsupported architecture: ${TARGETARCH}" >&2; exit 1 ;;
    esac
    curl -OL --no-progress-meter "https://github.com/sass/dart-sass/releases/download/${SASS_VERSION}/dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz"
    tar -xzf "dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz" -C /usr/local/bin --strip-components=1
    rm -f "dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz"
    curl -OL --no-progress-meter "https://github.com/swc-project/swc/releases/download/${SWC_VERSION}/swc-${SWC_ARCH}"
    mv "swc-${SWC_ARCH}" /usr/local/bin/swc
    chmod +x /usr/local/bin/swc
EOF

ENV COMPOSER_ALLOW_SUPERUSER=1

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link docker/web/frankenphp/conf.d/10-gewisdb.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/web/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/web/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]

# GEWISDB Development Image (local)
FROM gewisdb_web_base AS gewisdb_web_development

ARG USER_UID=1000
ARG USER_GID=1000

ENV APP_ENV=dev
ENV XDEBUG_MODE=off
ENV FRANKENPHP_WORKER_CONFIG=watch

RUN <<-EOF
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
    install-php-extensions xdebug
    # macOS `id -g` returns 20 (staff), which already exists in the base image.
    if ! getent group "$USER_GID" >/dev/null; then
        groupadd -g "$USER_GID" nonroot
    fi
    useradd -m -u "$USER_UID" -g "$USER_GID" -s /bin/bash nonroot
    chown -R "$USER_UID:$USER_GID" /data/caddy /config/caddy
    # `var/` and the upload directory are volumes, which take their ownership from the image; without this the
    # container cannot write its cache, its log or an uploaded file.
    mkdir -p /app/var/cache /app/var/log /app/public/data
    chown -R "$USER_UID:$USER_GID" /app
    git config --system --add safe.directory /app
EOF

COPY --link docker/web/frankenphp/conf.d/20-gewisdb.dev.ini $PHP_INI_DIR/app.conf.d/

USER nonroot

CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch" ]

# GEWISDB Production Builder
FROM gewisdb_web_base AS gewisdb_web_prod_builder

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/web/frankenphp/conf.d/20-gewisdb.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link composer.* symfony.* ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=docker/ . ./

RUN <<-EOF
    mkdir -p var/cache var/log
    composer dump-autoload --classmap-authoritative --no-dev
    composer dump-env prod
    composer run-script --no-dev post-install-cmd
    php bin/console sass:build
    php bin/console importmap:install
    php bin/console asset-map:compile
    chmod +x bin/console
    chmod -R g=u var
    sync
EOF

# GEWISDB Production Image
FROM gewisdb_web_base AS gewisdb_web_production

ENV APP_ENV=prod
ENV FRANKENPHP_WORKER_CONFIG=production

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/web/frankenphp/conf.d/20-gewisdb.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link --from=gewisdb_web_prod_builder /app /app
