#!/bin/bash

composer install
mkdir -p /var/www/html/logs/
/usr/sbin/apache2ctl -D FOREGROUND
