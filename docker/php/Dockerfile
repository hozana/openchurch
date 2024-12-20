FROM dunglas/frankenphp:php8.4-bookworm
LABEL org.opencontainers.image.authors="contact@hozana.org"

ARG USER=www-data
WORKDIR /var/www/html

RUN apt update && apt install -y \
    acl \
    autoconf \
    cron \
    logrotate \
    sudo \
    bash \
    procps \
    libzip-dev \
    libxslt-dev \
    libgmp-dev \
    && mkdir -p /var/www/html/var/ \
    && apt clean

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && chmod 755 /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    exif \
    xsl \
    pcntl \
    opcache \
    gmp

# add PECL extensions
RUN pecl install apcu && docker-php-ext-enable apcu

COPY src/ /var/www/html/src/
COPY public/ /var/www/html/public/
COPY migrations/ /var/www/html/migrations/
COPY config/ /var/www/html/config/
COPY bin/ /var/www/html/bin/
COPY assets/ /var/www/html/assets/
COPY templates/ /var/www/html/templates/
COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/
COPY symfony.lock /var/www/html/
COPY usr/local/bin/docker-php-entrypoint /usr/local/bin/
COPY .env /var/www/html/
COPY etc/cron.d/backend /etc/cron.d/backend

COPY etc/caddy/Caddyfile /etc/caddy/Caddyfile
COPY etc/logrotate.d/symfony /etc/logrotate.d/symfony

RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-scripts --no-progress --no-suggest --prefer-dist --no-interaction
RUN mkdir -p var/cache && mkdir -p var/log && mkdir -p var/cache/prod && chown -R ${USER}:${USER} var

# Ajouter la capacité supplémentaire de se lier aux ports 80 et 443
# Donner l'accès en écriture à /data/caddy et /config/caddy
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp && \
    chown -R ${USER}:${USER} /data/caddy && chown -R ${USER}:${USER} /config/caddy;

RUN rm -f /var/run/crond.pid

USER ${USER}

HEALTHCHECK --interval=5s --timeout=10s --start-period=15s --retries=55 CMD /usr/bin/curl -f http://localhost:2019/metrics || exit 1