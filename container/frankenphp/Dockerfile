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

COPY ./app/composer.json ./app/composer.lock /app/
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts

COPY ./app /app
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts \
    && composer dump-autoload --classmap-authoritative --no-dev --no-interaction

COPY ./container/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Fail fast if the final image ever misses the FrankenPHP binary.
RUN command -v frankenphp && frankenphp version
