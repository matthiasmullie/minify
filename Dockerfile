ARG VERSION=cli
FROM php:$VERSION

COPY . /var/www
WORKDIR /var/www

RUN apt-get update
RUN apt-get install --reinstall -y ca-certificates
RUN apt-get install -y zip unzip libzip-dev git
RUN docker-php-ext-install zip pcntl
RUN pecl install xdebug || pecl install xdebug-3.1.6 || pecl install xdebug-2.7.2 && docker-php-ext-enable xdebug || true
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install
