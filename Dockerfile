# Use official PHP image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Create data directory and set permissions
RUN mkdir -p data && \
    chmod 755 data && \
    chown -R www-data:www-data data

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache
COPY .htaccess /var/www/html/
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
