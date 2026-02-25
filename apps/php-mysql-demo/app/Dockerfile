FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Enable .htaccess (clean URLs)
COPY apache-htaccess.conf /etc/apache2/conf-available/htaccess.conf
RUN a2enconf htaccess

COPY index.php demo.php about.php products.php news.php contacts.php .htaccess /var/www/html/
COPY includes /var/www/html/includes
COPY css /var/www/html/css
COPY data /var/www/html/data

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
