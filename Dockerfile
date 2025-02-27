FROM php:8.1-fpm

WORKDIR /var/www

COPY . .

RUN apt-get update && apt-get install -y libpng-dev zip git unzip
RUN docker-php-ext-install pdo pdo_mysql

CMD ["php-fpm"]
