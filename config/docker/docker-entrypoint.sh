#!/usr/bin/env bash
set -e

#This file allows you to configure the current container
#Add something here if you need it

#Show PHP versions
echo "------------------------"
version=$(php -v | grep -Eo "PHP [0-9\.]+");
echo "PHP          --> $version"

version=`composer --version | grep -Po '\d.\d.\d '`
echo "Composer     --> $version"
echo "------------------------"

exec "$@"
