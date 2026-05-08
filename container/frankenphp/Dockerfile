FROM dunglas/frankenphp:php8.2-bookworm

RUN install-php-extensions \
    pdo_mysql \
    intl \
    ldap \
    zip \
    amqp

RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY ./container/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
