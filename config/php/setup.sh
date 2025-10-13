#!/bin/bash

composer install
chown -R www-data:www-data /var/www
mkdir -p /var/www/html/logs/
/usr/sbin/apache2ctl -D FOREGROUND
