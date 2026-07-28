# Single build file for every image of the project. Stages are selected with --target.
#   python  : synchro scripts        elastic : search engine
#   dev     : local PHP runtime      prod    : shipped PHP runtime (no dev dependencies)

ARG USER=www-data

# ============================================
# Stage: python-compiler
# Builds the virtualenv for the synchro scripts
# ============================================
FROM python:3.9-slim AS python-compiler
ENV PYTHONUNBUFFERED=1

WORKDIR /app/

RUN python -m venv /opt/venv
# Enable venv
ENV PATH="/opt/venv/bin:$PATH"

COPY ./scripts/requirements.txt /app/requirements.txt
RUN pip install -Ur requirements.txt

# ============================================
# Stage: python
# Runtime for the synchro scripts, driven by cron
# ============================================
FROM python:3.9-slim AS python
WORKDIR /app/

RUN apt update && apt install -y \
    curl \
    jq \
    cron\
    && apt clean

COPY --from=python-compiler /opt/venv /opt/venv
COPY etc/cron.d/python /etc/cron.d/python
COPY usr/local/bin/docker-python-entrypoint /usr/local/bin/docker-python-entrypoint

# Enable venv
ENV PATH="/opt/venv/bin:$PATH"
COPY ./scripts /app/

RUN rm -f /var/run/crond.pid

ENTRYPOINT ["/usr/local/bin/docker-python-entrypoint"]

# ============================================
# Stage: elastic
# The parish/diocese mappings need icu_collation_keyword,
# which the official image does not ship.
# ============================================
FROM elasticsearch:8.18.1 AS elastic

RUN bin/elasticsearch-plugin install --batch analysis-icu

# ============================================
# Stage: base
# Runtime shared by dev and prod, kept as small as the
# app allows.
# ============================================
FROM dunglas/frankenphp:php8.5-trixie AS base
LABEL org.opencontainers.image.authors="contact@hozana.org"

ARG USER
WORKDIR /var/www/html

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
    # Runtime, needed by the shipped image
    cron logrotate procps curl \
    libzip5 libxslt1.1 libgmp10 libicu76 \
    # Build only, purged at the end of this layer
    autoconf g++ make \
    libzip-dev libxslt-dev libgmp-dev libicu-dev \
    # opcache is already compiled statically into the base image, installing it would yield no module
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        exif \
        xsl \
        pcntl \
        intl \
        gmp \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    # gcc and friends ship with the base image itself and are marked manual, so they survive
    # autoremove unless named here. Nothing in the shipped image compiles anything.
    && apt-get remove --purge -y autoconf g++ gcc make \
        libzip-dev libxslt-dev libgmp-dev libicu-dev \
    && apt-get -y autoremove --purge \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* ~/.pearrc \
    && mkdir -p /var/www/html/var/

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY usr/local/bin/docker-php-entrypoint /usr/local/bin/
COPY etc/cron.d/backend /etc/cron.d/backend
COPY etc/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY etc/logrotate.d/symfony /etc/logrotate.d/symfony

# Allow binding to ports 80 and 443, and grant write access to /data/caddy and /config/caddy
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R ${USER}:${USER} /data/caddy \
    && chown -R ${USER}:${USER} /config/caddy \
    && rm -f /var/run/crond.pid

HEALTHCHECK --interval=5s --timeout=10s --start-period=15s --retries=55 CMD /usr/bin/curl -f http://localhost:2019/metrics || exit 1

# ============================================
# Stage: dev
# Everything prod deliberately leaves out: the toolchain
# (so extensions can still be built), xdebug, sudo, a
# database client, and the require-dev packages.
# ============================================
FROM base AS dev

ARG USER

RUN apt-get update && apt-get install -y --no-install-recommends \
    acl sudo bash mariadb-client \
    autoconf g++ make \
    libzip-dev libxslt-dev libgmp-dev libicu-dev \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/*

# Loaded but inert: turn it on per command, e.g. XDEBUG_MODE=coverage vendor/bin/phpunit
ENV XDEBUG_MODE=off

COPY composer.json composer.lock symfony.lock ./

RUN COMPOSER_MEMORY_LIMIT=-1 composer install \
        --no-scripts \
        --no-progress \
        --prefer-dist \
        --no-interaction \
    && rm -rf /root/.cache/composer \
    && mkdir -p var/cache var/log var/cache/dev \
    && chown -R ${USER}:${USER} var vendor

USER ${USER}

# ============================================
# Stage: prod
# Shipped image. No dev dependencies, no toolchain, no
# sudo, no composer. Manifests are installed before the
# sources are copied so app changes do not invalidate
# the vendor layer.
# ============================================
FROM base AS prod

ARG USER

COPY composer.json composer.lock symfony.lock ./

RUN COMPOSER_MEMORY_LIMIT=-1 composer install \
        --no-dev \
        --no-scripts \
        --no-progress \
        --prefer-dist \
        --no-interaction \
        --optimize-autoloader \
    && rm -rf /root/.cache/composer

COPY src/ /var/www/html/src/
COPY public/ /var/www/html/public/
COPY migrations/ /var/www/html/migrations/
COPY config/ /var/www/html/config/
COPY bin/ /var/www/html/bin/
COPY assets/ /var/www/html/assets/
COPY templates/ /var/www/html/templates/
COPY .env /var/www/html/

# The classmap above was built without the application classes; regenerate it, compile the
# assets, then drop composer: it is a build tool and has no business in the shipped image.
RUN mkdir -p var/cache var/log var/cache/prod \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && APP_ENV=prod APP_DEBUG=0 bin/console asset-map:compile \
    && rm -f /usr/local/bin/composer \
    && rm -rf /root/.cache/composer /tmp/* \
    && chown -R ${USER}:${USER} var public vendor

USER ${USER}
