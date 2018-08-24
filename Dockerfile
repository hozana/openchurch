FROM php:7.1-apache
MAINTAINER Hozana team

# Add necesary libraries
RUN apt-get update \
    && apt-get upgrade -y --force-yes \
    && apt-get install -y --force-yes \
        apt-transport-https \
        curl \
        git \
        rsyslog \
        supervisor \
        # needed for nodejs:
        gnupg \
        # needed for php's zip extensions:
        zlib1g-dev

# Add PHP extensions
RUN docker-php-ext-install mbstring pdo pdo_mysql zip

# Add Node.js for npm install
RUN curl -sL https://deb.nodesource.com/setup_8.x | bash -
RUN apt-get install -y --force-yes nodejs
RUN apt-get install -y --force-yes build-essential

# bugfix: remove cmdtest to install yarn correctly.
RUN apt-get remove -y cmdtest
# yarn package manager
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
  && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && apt-get install -y yarn

# add PECL extensions
# RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure PHP
#COPY ./tools/docker/backend/php.ini /usr/local/etc/php/

# Configure Apache
COPY ./config/docker/apache-vhost.conf /etc/apache2/sites-available/openchurch.conf
RUN a2dissite 000-default.conf
RUN a2ensite openchurch
RUN a2enmod rewrite
RUN a2enmod headers

# Configure supervisord and syslog
RUN mkdir -p /var/log/supervisor
COPY ./config/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./config/docker/rsyslog.conf /etc/rsyslog.conf

# Install Composer and make its cache directory world-writable
# as we will later run it under a local user id.
COPY ./config/docker/install_composer.sh /data/scripts/
RUN /data/scripts/install_composer.sh
RUN mkdir -p /.composer && chmod -R 777 /.composer

# Docker entrypoint
COPY ./config/docker/docker-entrypoint.sh /data/scripts/
ENTRYPOINT ["/data/scripts/docker-entrypoint.sh"]

#RUN mkdir -p /data/code
WORKDIR /var/www

VOLUME ["/var/www"]

EXPOSE 80

#Run supervisord to launch Apache
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
