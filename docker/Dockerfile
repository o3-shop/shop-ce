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

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Clean up to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/source|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<VirtualHost \*:80>/a <Directory /var/www/html/source>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' /etc/apache2/sites-available/000-default.conf

CMD ["docker/entrypoint.sh"]