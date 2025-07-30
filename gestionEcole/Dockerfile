FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev zip unzip git curl

RUN docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN chmod -R 755 storage bootstrap/cache

RUN php artisan config:cache || echo "Config cache failed"

RUN php artisan route:cache || echo "Route cache failed"

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=$PORT
