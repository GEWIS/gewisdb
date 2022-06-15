FROM php:7.1-fpm as gewisdb_web_development

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
        git \
        ghostscript \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libpng-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        nano \
        poppler-utils \
        rsync \
        ssh \
        sshpass \
        unzip \
        zip \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure \
        gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        calendar \
        curl \
        exif \
        gd \
        intl \
        opcache \
        pgsql \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip

RUN pecl install imagick \
    && docker-php-ext-enable imagick

RUN git clone -b 2.9.8 https://github.com/xdebug/xdebug.git /root/xdebug \
    && cd /root/xdebug && ./rebuild.sh

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/g' /etc/ImageMagick-6/policy.xml

WORKDIR /code

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        nodejs \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data ./composer.json ./
RUN php composer.phar install

COPY --chown=www-data:www-data ./docker/web/development/php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./docker/web/development/php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf
COPY --chown=www-data:www-data ./config/autoload/local.development.php.dist ./config/autoload/local.php
COPY --chown=www-data:www-data ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
COPY --chown=www-data:www-data ./config/autoload/laminas-developer-tools.local.php.dist ./config/autoload/laminas-developer-tools.local.php

COPY --chown=www-data:www-data ./docker/web/development/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab

COPY --chown=www-data:www-data ./docker/web/development/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod 0775 ./docker-entrypoint.sh

COPY --chown=www-data:www-data . /code

ENV PHP_IDE_CONFIG="serverName=database.gewis.nl"

RUN php composer.phar dump-autoload

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
