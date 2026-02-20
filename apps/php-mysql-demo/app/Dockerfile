FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

COPY index.php /var/www/html/index.php
COPY about.html /var/www/html/about.html
COPY status.html /var/www/html/status.html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
