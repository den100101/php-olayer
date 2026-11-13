
FROM php:8.3-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/display_errors = On/display_errors = Off/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create upload directory
RUN mkdir -p /var/www/html/uploads/instructors \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
