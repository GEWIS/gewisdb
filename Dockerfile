FROM php:5.6-fpm
LABEL org.opencontainers.image.source https://github.com/GEWIS/gewisdb
WORKDIR /code

# Install required software.
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libicu-dev \
        g++ \
        zlib1g-dev \
        libpq-dev \
        git \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install soap \
    && docker-php-ext-install zip \
    && docker-php-ext-install calendar \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP.
COPY ./docker/php.ini /usr/local/etc/php/conf.d/default.ini

# Install dependencies.
COPY ./composer.json ./composer.lock ./
RUN composer install --no-scripts --optimize-autoloader --no-dev

# Install application.
COPY . .

VOLUME ["/code/data", "/public"]
CMD cp -r /code/public /public && php-fpm
