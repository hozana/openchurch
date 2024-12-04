FROM dunglas/frankenphp
LABEL org.opencontainers.image.authors="contact@hozana.org"

ARG USER=www-data
ARG SERVER_NAME=rosario.local

WORKDIR /var/www/html

RUN apt update && apt install -y \
    acl \
    autoconf \
    cron \
    ffmpeg \
    gawk \
    g++ \ 
    make \
    libnss3-tools \
    libpng-dev \
    libexif-dev \
    libxpm-dev \
    libxml2-dev \
    libxslt-dev \
    libwebp-dev \
    libzip-dev \
    libgmp-dev \
    libjpeg-dev \
    logrotate \
    nodejs \
    npm \
    sudo \
    bash \
    procps \
    && mkdir -p /var/www/html/var/ \
    && apt clean

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && chmod 755 /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-configure gmp \
    && docker-php-ext-configure intl

RUN docker-php-ext-install \
    pdo_mysql \
    gd \
    intl \
    zip \
    exif \
    xsl \
    pcntl \
    opcache \
    gmp

# add PECL extensions
RUN pecl install redis && docker-php-ext-enable redis && \
    pecl install apcu && docker-php-ext-enable apcu && \
    pecl install xdebug && docker-php-ext-enable xdebug
    #pecl install amqp && docker-php-ext-enable amqp && \

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
COPY .env.test /var/www/html/

# The following line is needed only for load tests
COPY tests/ /var/www/html/tests/
COPY etc/caddy/Caddyfile /etc/caddy/Caddyfile
COPY etc/logrotate.d/symfony /etc/logrotate.d/symfony
RUN mkdir -p var/{cache,log} && mkdir -p var/cache/prod && chown -R ${USER}:${USER} var

RUN \
 # Ajouter la capacité supplémentaire de se lier aux ports 80 et 443
 setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp &&\
 # Donner l'accès en écriture à /data/caddy et /config/caddy
 chown -R ${USER}:${USER} /data/caddy && chown -R ${USER}:${USER} /config/caddy;

RUN rm -f /var/run/crond.pid

USER ${USER}

HEALTHCHECK --interval=5s --timeout=10s --start-period=15s --retries=55 CMD /usr/bin/curl -f http://localhost:2019/metrics || exit 1