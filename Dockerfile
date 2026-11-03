ARG VERSION=cli
FROM php:$VERSION

COPY . /var/www
WORKDIR /var/www

RUN cat /etc/os-release | grep jessie && echo "deb http://archive.debian.org/debian jessie main" > /etc/apt/sources.list || true
RUN cat /etc/os-release | grep stretch && echo "deb http://archive.debian.org/debian stretch main" > /etc/apt/sources.list || true
RUN cat /etc/os-release | grep buster && echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list || true
RUN cat /etc/os-release | grep bullseye && echo "deb http://archive.debian.org/debian bullseye main" > /etc/apt/sources.list || true
RUN apt update --allow-insecure-repositories
RUN apt install --reinstall -y --allow-unauthenticated ca-certificates
RUN apt install -y --allow-unauthenticated zip unzip libzip-dev git
RUN docker-php-ext-install zip pcntl
RUN pecl install xdebug || pecl install xdebug-3.1.6 || pecl install xdebug-2.7.2 && docker-php-ext-enable xdebug || true
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install
