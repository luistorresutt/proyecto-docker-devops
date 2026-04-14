FROM php:8.1-apache

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar el código a Apache
COPY src/ /var/www/html/

# Dar permisos
RUN chown -R www-data:www-data /var/www/html
