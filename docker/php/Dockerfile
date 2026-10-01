# PHP-FPM для разработки и запуска приложения.
FROM php:8.2-fpm-alpine

# Системные пакеты, нужные для сборки PHP-расширений.
RUN apk add --no-cache \
        git \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libzip-dev

# Расширения:
#   pdo_mysql  - работа с MySQL
#   mbstring   - корректная работа с UTF-8
#   gd         - ресайз изображений
#   fileinfo   - определение MIME загружаемых файлов
#   zip        - распаковка пакетов Composer
#   opcache    - ускорение исполнения PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        gd \
        fileinfo \
        zip \
        opcache


# Включаем opcache и задаём часовой пояс.
RUN docker-php-ext-enable opcache \
    && printf "date.timezone = Europe/Moscow\nopcache.enable = 1\nopcache.validate_timestamps = 1\nopcache.revalidate_freq = 0\n" > /usr/local/etc/php/conf.d/custom.ini

# Composer из официального образа.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Создаём папки, которые нужны приложению на рантайме.
RUN mkdir -p storage/logs public/uploads public/css \
    && chown -R www-data:www-data storage public/uploads public/css

# Git в контейнере не доверяет папке проекта из bind-mount (UID не совпадает).
# Разрешаем работу с ней для всех пользователей контейнера.
RUN git config --system --add safe.directory /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
