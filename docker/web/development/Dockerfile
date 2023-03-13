# Compile the language files
FROM alpine:latest AS gewisdb_translations
WORKDIR /code

RUN apk add --no-cache --virtual .runtime-deps \
        gettext

COPY ./module/Application/language/*.po ./

RUN msgfmt en.po -o en -c --strict -v \
    && msgfmt nl.po -o nl -c --strict -v

# Install dependencies
FROM php:8.3-fpm-alpine AS gewisdb_web_development_base
WORKDIR /code

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        icu-dev \
        libmemcached-dev \
        libpq-dev \
        libzip-dev \
        linux-headers \
        openldap-dev \
        sqlite-dev \
        tzdata \
    && apk add --no-cache --virtual .runtime-programs \
        git \
        icu-data-full \
        nano \
        unzip \
        zip \
    && docker-php-ext-install -j$(nproc) \
        calendar \
        curl \
        intl \
        ldap \
        opcache \
        pgsql \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -r /tmp/pear \
    && runtimeDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )" \
    && apk add --virtual .gewisdb-runtime-deps $runtimeDeps \
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && apk del .build-deps

# Install composer and dependencies.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./composer.json ./composer.lock ./

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install

# Create the final image.
FROM gewisdb_web_development_base AS gewisdb_web_development

COPY --chown=www-data:www-data ./docker/web/development/php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./docker/web/development/php-fpm.conf /usr/local/etc/php-fpm.d/zz-gewisdb.conf
COPY --chown=www-data:www-data ./config/autoload/local.development.php.dist ./config/autoload/local.php
COPY --chown=www-data:www-data ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
COPY --chown=www-data:www-data ./config/autoload/laminas-developer-tools.local.php.dist ./config/autoload/laminas-developer-tools.local.php

COPY --chown=www-data:www-data ./docker/web/development/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab

COPY --chown=www-data:www-data ./docker/web/development/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod 0775 ./docker-entrypoint.sh

COPY --chown=www-data:www-data . /code
COPY --chown=www-data:www-data --from=gewisdb_translations /code/*.mo /code/module/Application/language/

RUN chmod 0775 ./orm ./web

ENV PHP_IDE_CONFIG="serverName=database.gewis.nl"

RUN composer dump-autoload

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
