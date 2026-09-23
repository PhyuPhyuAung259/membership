FROM php:8.4-fpm-alpine

# System deps + PHP extensions Laravel commonly needs
RUN apk add --no-cache \
        nginx \
        bash \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        postgresql-dev \
        nodejs \
        npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first so Docker can cache this layer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Build front-end assets (delete this block if your app has no Vite/npm build)
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; fi

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && if [ -f package.json ] && [ -d node_modules ]; then npm run build && rm -rf node_modules; fi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
