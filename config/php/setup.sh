#!/bin/bash

composer install
mkdir -p /var/www/html/logs/
#this needs to go in setup.sh because RUN chown doesn't play nice with volumes
chown -R www-data:www-data /var/www
/usr/sbin/apache2ctl -D FOREGROUND
