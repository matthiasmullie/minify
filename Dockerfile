ARG version=cli
FROM php:$version

COPY . /var/www
WORKDIR /var/www

RUN apt-get update
RUN apt-get install --reinstall -y ca-certificates
RUN apt-get install -y zip unzip libzip-dev git
RUN docker-php-ext-install zip pcntl
RUN pecl install xdebug && docker-php-ext-enable xdebug || true
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN composer install
