# Use a PHP image with Apache
FROM php:8.1-apache

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y

# Install necessary PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libxml2-dev libzip-dev libjpeg-dev libpng-dev libfreetype6-dev mariadb-client \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache bcmath soap

RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd

# Enable Apache mod_rewrite for Symfony
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/source
RUN sed -ri -e "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s|/var/www/|/var/www/html/|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files to the container
COPY . .

RUN git config --global --add safe.directory /var/www/html

# Install Composer
COPY --from=composer:2.2.0 /usr/bin/composer /usr/bin/composer

# Install project dependencies
RUN composer install --optimize-autoloader

# Set proper permissions for Symfony cache and logs
RUN  chown -R root:www-data /var/www/html/var && \
     chown -R root:www-data /var/www/html/source && \
     chown -R root:www-data /var/www/html/.env

RUN chmod -R 2775 /var/www/html/var && \
    chmod -R 2775 /var/www/html/source && \
    chmod -R 2775 /var/www/html/.env

# Expose port 80 for web traffic
EXPOSE 80

# Start Apache server
CMD ["docker/entrypoint.sh"]
