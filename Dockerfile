FROM php:8.2-fpm

# Устанавливаем системные зависимости для Postgres (libpq-dev)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    libfcgi-bin \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer 

WORKDIR /var/www/mysite

# Кэшируем зависимости
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist --no-dev

# Копируем код
COPY . .
RUN composer dump-autoload --optimize

# Права на файлы (в проде лучше не давать всё www-data, но для начала ок)
RUN chown -R www-data:www-data /var/www/mysite

# init_db.php должен быть доступен даже если /var/www/mysite замонтирован volume'ом
COPY .docker/init_db.php /init_db.php

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    # make sure script uses Unix line endings so /bin/bash shebang works in container
    && sed -i 's/\r$//' /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]