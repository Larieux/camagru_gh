
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/camagru.ini

WORKDIR /var/www/html

COPY . .

RUN mkdir -p /var/www/html/storage/images \
    && chown -R www-data:www-data /var/www/html/storage

EXPOSE 9000
