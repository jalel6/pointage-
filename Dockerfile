FROM php:8.2-apache

# Copier configuration Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Installer extensions PHP nécessaires (PDO MySQL + mysqli)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite
RUN a2enmod rewrite

# Copier le projet dans Apache
COPY . /var/www/html

# Donner les permissions à Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
