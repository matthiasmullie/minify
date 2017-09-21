FROM php:cli

# install composer and a bunch of dependencies
RUN apt-get update && apt-get install -y git curl zip unzip zlib1g-dev
RUN docker-php-ext-install zip
RUN docker-php-ext-install pcntl
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# pull in code
WORKDIR /var/www
COPY . .

# install dependencies
RUN composer install

# to support loading the directory as volume, we'll move vendor out of the way so it
# doesn't get overwritten by more recent code; we'll put it back before running anything
RUN mv vendor ../vendor
RUN echo 'cp -r /var/vendor /var/www/vendor && exec "$@"' > /etc/run.sh
RUN chmod +x /etc/run.sh
ENTRYPOINT ["/bin/sh", "/etc/run.sh"]

CMD ["vendor/bin/phpunit"]
