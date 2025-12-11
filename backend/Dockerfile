FROM php:8.2-apache

RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite && rm -rf /var/lib/apt/lists/*

# Copy backend app into webroot
WORKDIR /var/www/html
COPY . /var/www/html

# Use the `public` directory as DocumentRoot
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/www/html/data && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
