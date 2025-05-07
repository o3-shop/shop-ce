cp .env.example .env

cp source/config.inc.php.dist source/config.inc.php

yes | composer install --no-interaction --optimize-autoloader

chown -R root:www-data /var/www/html/var && \
chown -R root:www-data /var/www/html/source && \
chown -R root:www-data /var/www/html/.env


chmod -R 2775 /var/www/html/var && \
chmod -R 2775 /var/www/html/source && \
chmod -R 2775 /var/www/html/.env

clear
a2enmod rewrite
apache2-foreground