FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Configure & Install PHP extensions required by EspoCRM
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    intl \
    bcmath \
    exif \
    opcache \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring

# Enable required Apache modules
RUN a2enmod rewrite headers expires mime

# Copy custom configurations
COPY docker/php.ini $PHP_INI_DIR/conf.d/espocrm.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html/

# Copy entrypoint script and make executable
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /var/www/html/bin/command \
    && chown -R www-data:www-data /var/www/html

# Expose ports (Render maps dynamically via $PORT, default 10000)
EXPOSE 10000 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
