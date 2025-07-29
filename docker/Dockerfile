FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libzip-dev \
    libssl-dev \
    zlib1g-dev \
    libicu-dev \
    libxslt1-dev \
    libbz2-dev \
    libreadline-dev \
    g++ \
    unzip \
    git \
    nano \
    sudo \
    wget \
    && apt-get install -y default-mysql-client

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        curl \
        bcmath \
        soap \
        bz2 \
        intl \
        opcache \
        calendar \
        exif \
        zip

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN echo "date.timezone = Europe/Berlin" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = 11000M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/upload-size.ini \
    && echo "post_max_size = 100M" > /usr/local/etc/php/conf.d/post-size.ini

# Php Error Logging
RUN echo "error_reporting = E_ALL" > /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "error_log = /tmp/php_errors.log" >> /usr/local/etc/php/conf.d/error-reporting.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Clean up to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

# Install PHP CS Fixer globally
RUN composer global require friendsofphp/php-cs-fixer \
    && ln -s /root/.composer/vendor/bin/php-cs-fixer /usr/local/bin/php-cs-fixer

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/source|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<VirtualHost \*:80>/a <Directory /var/www/html/source>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' /etc/apache2/sites-available/000-default.conf

CMD ["docker/entrypoint.sh"]