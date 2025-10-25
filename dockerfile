# Use official PHP image with Apache
FROM php:8.2-apache

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set permissions (optional, for Linux environments)
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80
