FROM php:8.3-apache

# Install dependencies for PHP, mPDF (GD), and QR Codes
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# Enable Apache Mod Rewrite for API routing
RUN a2enmod rewrite

# Update Apache DocumentRoot to point directly to /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Expose port 80 for Render
EXPOSE 80