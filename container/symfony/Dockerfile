FROM php:8.2.1-fpm
RUN apt-get update && \
    apt-get install libldap2-dev libicu-dev librabbitmq-dev -y git && \
    docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ && \
    docker-php-ext-install ldap pdo_mysql intl && \
    pecl install amqp && docker-php-ext-enable amqp && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
 RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
 RUN apt-get install -y symfony-cli
RUN apt-get install -y  libzip-dev zip && \ 
        docker-php-ext-install zip

# COPY ./container/symfony/php-memory-limit.ini /usr/local/etc/php/conf.d/99-memory-limit.ini

# COPY ./container/symfony/php-opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

# COPY ./container/symfony/opcache-preload.php /usr/local/etc/php/opcache-preload.php

COPY ./container/symfony/php-fpm-pool-performance.conf /usr/local/etc/php-fpm.d/zz-www-performance.conf

Copy ./ /app
WORKDIR /app

RUN usermod -u 1000 www-data
RUN usermod -G 100 www-data


EXPOSE 9000
