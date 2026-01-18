FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        zlib1g-dev \
        libicu-dev \
        g++ \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl zip xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install Symfony CLI (optional, for local dev)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose port for Symfony server
EXPOSE 8081

CMD ["sh", "-c", "if [ ! -d public ]; then mkdir -p public && echo '<?php phpinfo();' > public/index.php; fi && php -S 0.0.0.0:8081 -t public"]
