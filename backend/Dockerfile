FROM php:8.0-cli

WORKDIR /app

# Install system deps and PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /app

RUN composer install --no-interaction --prefer-dist --no-progress

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
