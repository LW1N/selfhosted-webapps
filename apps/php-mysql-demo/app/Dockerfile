FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

COPY index.php /var/www/html/index.php
COPY humanturtle.html /var/www/html/humanturtle.html
COPY aiturtle.html /var/www/html/aiturtle.html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
