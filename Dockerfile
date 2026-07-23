FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    mbstring \
    opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Configure PHP for production
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /usr/local/etc/php/php.ini
RUN sed -i 's/post_max_size = 8M/post_max_size = 12M/' /usr/local/etc/php/php.ini
RUN sed -i 's/max_execution_time = 30/max_execution_time = 120/' /usr/local/etc/php/php.ini

EXPOSE 80

CMD ["apache2-foreground"]
