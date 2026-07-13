FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG WWWUSER=1000
ARG WWWGROUP=1000
RUN groupadd -g ${WWWGROUP} app \
    && useradd -u ${WWWUSER} -g app -m app

USER app
WORKDIR /var/www/html
