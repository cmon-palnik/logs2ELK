#!/bin/bash
set -eux
cd /var/www/html/

wp core is-installed --allow-root || \
    wp core install --allow-root --url=localhost:8080 --title=Logs2ELK \
        --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com

chown -R www-data:www-data /var/www/html

exec "$@"
