ARG ALPINE_VERSION="3.23"
ARG BASE="alpine:$ALPINE_VERSION"

ARG PHP_MAJOR="8"
ARG PHP_MINOR="3"
ARG PHP_VERSION=$PHP_MAJOR$PHP_MINOR

ARG COMPOSER_VERSION="2.9"

ARG BUILDTYPE="development"

# Compile the language files (shared between dev and prod)
# uses the php image to reduce number of images that need to be pulled
FROM ${BASE} AS gewisdb_translations
WORKDIR /translations

RUN --mount=type=cache,target=/var/cache/apk,sharing=locked \
    apk add --virtual .runtime-deps \
        gettext

COPY ./module/Application/language/*.po ./

RUN msgfmt en.po -o en -c --strict -v \
    && msgfmt nl.po -o nl -c --strict -v

FROM composer:${COMPOSER_VERSION} AS gewisdb_composer

# Install dependencies
FROM ${BASE} AS gewisdb_web_development_base
ARG PHP_VERSION

WORKDIR /code

RUN --mount=type=cache,target=/var/cache/apk,sharing=locked \
    --mount=type=cache,target=/tmp/pear,sharing=locked \
    apk add --virtual .build-deps \
        tzdata \
    && apk add --virtual .runtime-programs \
        icu-data-full \
        git \
        nano \
        php${PHP_VERSION} \
        php${PHP_VERSION}-calendar \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-iconv \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-openssl \
        php${PHP_VERSION}-pdo_pgsql \
        php${PHP_VERSION}-pecl-xdebug \
        php${PHP_VERSION}-phar \
        php${PHP_VERSION}-session \
        php${PHP_VERSION}-simplexml \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-xmlwriter \
    && runtimeDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )" \
    && apk add --virtual .gewisdb-runtime-deps $runtimeDeps \
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && apk del .build-deps \
    && ln -s ./php-fpm${PHP_VERSION} /usr/sbin/php-fpm \
    && ln -s ./php${PHP_VERSION} /usr/bin/php \
    && rm /etc/php${PHP_VERSION}/php-fpm.d/www.conf \
    && rm /etc/crontabs/root

RUN adduser -S --uid 82 -D -H www-data -G www-data

# Copy composer over (needed for replenish)
COPY --from=gewisdb_composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN --mount=type=cache,target=/root/.composer,sharing=locked \
    --mount=from=gewisdb_composer,source=/usr/bin/composer,target=/usr/bin/composer \
    --mount=source=./composer.json,target=/code/composer.json \
    --mount=source=./composer.lock,target=/code/composer.lock \
    composer install

# Create the final image.
FROM gewisdb_web_development_base AS gewisdb_web_development
ARG BUILDTYPE

COPY ./docker/web/${BUILDTYPE}/php.ini /etc/php${PHP_VERSION}/php.ini
COPY ./docker/web/${BUILDTYPE}/php-fpm.conf /etc/php${PHP_VERSION}/php-fpm.d/zz-gewisdb.conf
COPY ./config/autoload/local.${BUILDTYPE}.php.dist ./config/autoload/local.php
COPY ./config/autoload/doctrine.local.${BUILDTYPE}.php.dist ./config/autoload/doctrine.local.php
COPY ./config/autoload/laminas-developer-tools.local.php.dist ./config/autoload/laminas-developer-tools.local.php

RUN --mount=source=./docker/web/${BUILDTYPE}/crontab,target=/tmp/crontab crontab -u www-data /tmp/crontab \
    && chmod 0755 /etc/crontabs/www-data

COPY ./docker/web/${BUILDTYPE}/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod +x ./docker-entrypoint.sh

COPY --link . /code
COPY --from=gewisdb_translations /translations/*.mo /code/module/Application/language/

RUN chmod +x ./orm ./web

ENV PHP_IDE_CONFIG="serverName=database.gewis.nl"

RUN --mount=from=gewisdb_composer,source=/usr/bin/composer,target=/usr/bin/composer \
    composer dump-autoload

# Set up a persistent data volumes
VOLUME ["/data/logs"]
RUN mkdir -p "/data/logs" && \
    chown -R www-data:www-data /data/logs

# Other container-writable files and directories (preparation for non-root containers)
RUN rm -rf /code/data \
    && ln -s /data /code/data \
    && mkdir -p "/data/cache" \
    && chown -R www-data:www-data /data/cache \
    && mkdir -p "/data/DoctrineORMModule" \
    && chown -R www-data:www-data /data/DoctrineORMModule \
    && touch config/bash.env \
    && chown www-data:www-data config/bash.env

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
