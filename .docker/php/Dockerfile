FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN docker-php-ext-enable opcache

CMD ["php-fpm"]
