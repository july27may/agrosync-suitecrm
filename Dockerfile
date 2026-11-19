FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
       libicu-dev libonig-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
       unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
       bcmath curl exif gd intl mbstring mysqli pdo_mysql soap xml zip opcache \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-suitecrm.ini
WORKDIR /var/www/html
