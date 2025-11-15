FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libpng-dev \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Expose php-fpm port (internal)
EXPOSE 9000

# Default command (php-fpm will be run by container)
CMD ["php-fpm"]
