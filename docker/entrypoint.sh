cp .env.example .env

cp source/config.inc.php.dist source/config.inc.php

wget https://github.com/o3-shop/wave-theme/archive/refs/heads/main.zip

unzip main.zip

cp -r wave-theme-main/out/* source/out/

rm -rf wave-theme-main/out

cp -r wave-theme-main/* source/Application/views/wave


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