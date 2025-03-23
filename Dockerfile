# Use PHP 7.4 FPM as the base image
FROM php:7.4-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libz-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring pdo_mysql tokenizer xml exif \
    && docker-php-ext-enable exif \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a custom PHP configuration file to set upload limits
# RUN echo "upload_max_filesize=16M\npost_max_size=16M" > /usr/local/etc/php/conf.d/uploads.ini

# Copy Laravel application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Install Node dependencies
RUN npm install && npm run prod

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Set permissions for storage and cache
RUN chmod -R 777 storage bootstrap/cache

# Expose port 9000 (PHP-FPM)
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
