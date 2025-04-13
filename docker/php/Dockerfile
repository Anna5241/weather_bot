FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        git \
        unzip \
        sqlite3 \
        libsqlite3-dev \
        libzip-dev \
        libssl-dev \
        supervisor \
        pkg-config && \
    rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo pdo_sqlite zip

# Установка Redis (если требуется)
RUN pecl install -o -f redis && \
    rm -rf /tmp/pear && \
    docker-php-ext-enable redis

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www
COPY . .

# Установка зависимостей проекта
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Настройка прав
RUN chown -R www-data:www-data storage && \
    chmod -R 775 storage

# Копирование конфигурации Supervisor
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

RUN sed -i 's/^listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf


CMD ["supervisord", "-n"]
