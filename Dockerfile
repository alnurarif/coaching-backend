FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 777 storage bootstrap/cache \
    && rm -f bootstrap/cache/*.php

EXPOSE 8000

CMD sh -c "php artisan config:clear && php artisan route:clear && php artisan cache:clear && php artisan migrate:fresh --force --seeder=DemoDataSeeder && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
