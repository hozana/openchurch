#!/usr/bin/env bash

#Show PHP versions
echo "----- versions"
version=$(php -v | grep -Eo "PHP [0-9\.]+");
echo "PHP          --> $version"
version=`composer --version | grep -Po '\d.\d.\d '`
echo "Composer     --> $version"

# remove composer complaint about being run as root, we're inside a docker container it's ok
export COMPOSER_ALLOW_SUPERUSER=1

echo "----- install openchurch"
cd /data/code && composer install --no-interaction || exit 1
echo "----- migrate database"
cd /data/backend && ./bin/console doctrine:migrations:migrate latest || exit 1

# fix access rights to cache:
# our composer installs created them with root owner, while apache runs as www
chmod -R 777 /data/backend/var/cache/dev

apache2-foreground
