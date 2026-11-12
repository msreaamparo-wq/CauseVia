FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN printf 'DirectoryIndex landingpage.php index.php index.html\n' \
    > /etc/apache2/conf-available/causevia.conf \
    && a2enconf causevia \
    && mkdir -p /var/www/html/uploads/projects \
       /var/www/html/alumni/uploads/proofs \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/alumni/uploads \
    && chmod -R 775 /var/www/html/uploads /var/www/html/alumni/uploads

EXPOSE 80

CMD ["apache2-foreground"]
