mv .env.example .env

composer install --no-interaction --optimize-autoloader

chown -R root:www-data /var/www/html/var && \
chown -R root:www-data /var/www/html/source && \
chown -R root:www-data /var/www/html/.env


chmod -R 2775 /var/www/html/var && \
chmod -R 2775 /var/www/html/source && \
chmod -R 2775 /var/www/html/.env

clear

apache2-foreground