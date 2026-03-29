FROM php:8.4-fpm 
 
WORKDIR /var/www/html 
 
RUN apt-get update && apt-get install -y \ 
   libpng-dev \ 
   libjpeg-dev \ 
   libfreetype6-dev \ 
   libzip-dev \ 
   libpq-dev \ 
   unzip \ 
   git \ 
   curl \ 
   libxml2-dev \ 
&& docker-php-ext-configure gd --with-freetype --with-jpeg \ 
&& docker-php-ext-install pdo_mysql pdo_pgsql gd zip pcntl bcmath \ 
&& rm -rf /var/lib/apt/lists/* 
 
RUN pecl install redis && docker-php-ext-enable redis 
 
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer 
 
COPY . . 
 
RUN cp .env.example .env

# Install dependencies without scripts 
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --verbose 
 
RUN chown -R www-data:www-data storage bootstrap/cache \ 
&& chmod -R 775 storage bootstrap/cache 
 
EXPOSE 9000 