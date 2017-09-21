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
RUN rm -rf vendor
RUN composer install

# to support loading the directory as volume, we'll move vendor out of the way so it
# doesn't get overwritten by more recent code; we'll put it back before running anything
RUN mv vendor ../docker-vendor
RUN echo 'mv /var/www/vendor /var/current-vendor 2>/dev/null || : && \
    mv /var/docker-vendor /var/www/vendor && \
    /bin/sh -c "$@" || : && \
    mv /var/www/vendor /var/docker-vendor && \
    mv /var/current-vendor /var/www/vendor 2>/dev/null || :' > /etc/run.sh
ENTRYPOINT ["/bin/sh", "/etc/run.sh"]

CMD ["vendor/bin/phpunit"]
