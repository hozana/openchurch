# Docker Image for the Apache + OpenChurch API
# needs php7.1, Apache2.4

FROM php:7.1-apache
MAINTAINER Hozana team

# add necesary libraries and php extensions
RUN apt-get update \
    && apt-get install -y
        git \
    && docker-php-ext-install \
        mysqli \
        pdo \
        pdo_mysql

# install Composer
RUN \
    COMPOSER_SIG=$(curl -L https://composer.github.io/installer.sig) \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('SHA384', 'composer-setup.php') === '$COMPOSER_SIG') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

COPY ./config/Docker/php.ini /usr/local/etc/php/

COPY ./config/Docker/openchurch.conf /etc/apache2/sites-available/openchurch.conf
RUN a2ensite openchurch
RUN a2dissite 000-default

# enable necesary Apache modules
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod proxy
RUN a2enmod proxy_connect
RUN a2enmod proxy_http
RUN a2enmod proxy_wstunnel

WORKDIR /data/code
VOLUME ["/data/code"]

COPY ./entrypoint.sh /usr/local/bin/
CMD entrypoint.sh
