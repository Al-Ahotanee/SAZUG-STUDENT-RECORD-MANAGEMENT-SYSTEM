FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Configure Apache with security and performance headers
RUN echo '<IfModule mod_headers.c>\n\
    Header always set X-Content-Type-Options "nosniff"\n\
    Header always set X-Frame-Options "SAMEORIGIN"\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
    Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
</IfModule>' > /etc/apache2/conf-enabled/security-headers.conf

# Configure Apache to allow .htaccess in document root
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files first
COPY . /var/www/html/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (skip dev)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true; fi

# Create required upload directories
RUN mkdir -p uploads/passports uploads/documents uploads/certificates \
    && chmod -R 755 uploads

# Set ownership
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure PHP for production
RUN echo "expose_php = Off" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/sazug.ini \
    && echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/sazug.ini

# Expose port 80
EXPOSE 80
