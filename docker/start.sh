#!/bin/bash
service cron start
service apache2 start

php /var/www/myorders/composer.phar update

sleep infinity
