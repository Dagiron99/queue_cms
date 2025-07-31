FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    locales \
    cron \
    nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Настройка локалей
RUN sed -i -e 's/# ru_RU.UTF-8 UTF-8/ru_RU.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen

# Установка переменных окружения
ENV LANG ru_RU.UTF-8
ENV LANGUAGE ru_RU:ru
ENV LC_ALL ru_RU.UTF-8
ENV COMPOSER_ALLOW_SUPERUSER 1

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache json

# Настройка OPcache
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Включение mod_rewrite и mod_headers для Apache
RUN a2enmod rewrite headers

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание кастомного php.ini
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'post_max_size = 100M'; \
    echo 'upload_max_filesize = 100M'; \
    echo 'max_execution_time = 300'; \
    echo 'date.timezone = Europe/Moscow'; \
    echo 'default_charset = "UTF-8"'; \
    echo 'mbstring.http_input = UTF-8'; \
    echo 'mbstring.http_output = UTF-8'; \
    echo 'mbstring.language = Russian'; \
    echo 'error_reporting = E_ALL'; \
    echo 'display_errors = On'; \
    echo 'display_startup_errors = On'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Настройка cron
COPY crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && \
    crontab /etc/cron.d/crontab

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование composer.json и composer.lock (если есть)
COPY composer.* ./

# Установка зависимостей Composer (без dev-зависимостей)
RUN composer install --no-dev --no-interaction --optimize-autoloader || true

# Копирование остальных файлов проекта
COPY . .

# Настройка прав доступа и логов
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/temp \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && touch /var/log/php_errors.log \
    && chown www-data:www-data /var/log/php_errors.log

# Настройка документа Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Запуск cron и Apache
CMD service cron start && apache2-foreground

EXPOSE 80