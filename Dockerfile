FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libcurl4-openssl-dev unzip git \
    && docker-php-ext-install -j2 mbstring curl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/tw2.ini
WORKDIR /app
