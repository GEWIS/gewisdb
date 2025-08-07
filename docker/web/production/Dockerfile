ARG BASE="alpine:latest"
ARG PHPVERSION="php83"
ARG BUILDTYPE="production"

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

# Install dependencies
FROM ${BASE} AS gewisdb_web_production_base
ARG PHPVERSION

WORKDIR /code

RUN --mount=type=cache,target=/var/cache/apk,sharing=locked \
    --mount=type=cache,target=/tmp/pear,sharing=locked \
    apk add --virtual .build-deps \
        tzdata \
    && apk add --virtual .runtime-programs \
        git \
        ${PHPVERSION} \
        ${PHPVERSION}-calendar \
        ${PHPVERSION}-curl \
        ${PHPVERSION}-dom \
        ${PHPVERSION}-fpm \
        ${PHPVERSION}-iconv \
        ${PHPVERSION}-intl \
        ${PHPVERSION}-ldap \
        ${PHPVERSION}-mbstring \
        ${PHPVERSION}-opcache \
        ${PHPVERSION}-openssl \
        ${PHPVERSION}-pdo_pgsql \
        ${PHPVERSION}-phar \
        ${PHPVERSION}-session \
        ${PHPVERSION}-simplexml \
        ${PHPVERSION}-tokenizer \
        ${PHPVERSION}-xml \
        ${PHPVERSION}-xmlwriter \
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
    && ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm \
    && rm /etc/${PHPVERSION}/php-fpm.d/www.conf \
    && rm /etc/crontabs/root

RUN adduser -S --uid 82 -D -H www-data -G www-data

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN --mount=type=cache,target=/root/.composer,sharing=locked \
    --mount=from=composer:latest,source=/usr/bin/composer,target=/usr/bin/composer \
    --mount=source=./composer.json,target=/code/composer.json \
    --mount=source=./composer.lock,target=/code/composer.lock \
    composer install -o --no-dev

# Create the final image.
FROM gewisdb_web_production_base AS gewisdb_web_production
ARG BUILDTYPE

COPY ./docker/web/${BUILDTYPE}/php.ini /etc/${PHPVERSION}/php.ini
COPY ./docker/web/${BUILDTYPE}/php-fpm.conf /etc/${PHPVERSION}/php-fpm.d/zz-gewisdb.conf
COPY ./config/autoload/local.${BUILDTYPE}.php.dist ./config/autoload/local.php
COPY ./config/autoload/doctrine.local.${BUILDTYPE}.php.dist ./config/autoload/doctrine.local.php

RUN --mount=source=./docker/web/${BUILDTYPE}/crontab,target=/tmp/crontab crontab -u www-data /tmp/crontab \
    && chmod 0755 /etc/crontabs/www-data

COPY ./docker/web/${BUILDTYPE}/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod +x ./docker-entrypoint.sh

COPY --link . /code
COPY --from=gewisdb_translations /translations/*.mo /code/module/Application/language/

RUN chmod +x ./orm ./web

RUN --mount=from=composer:latest,source=/usr/bin/composer,target=/usr/bin/composer \
    composer dump-autoload -a --no-dev

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
