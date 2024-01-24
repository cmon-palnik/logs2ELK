#!/bin/bash
set -eux
cd /var/www/html/

wp core is-installed --allow-root || wp core install --allow-root --url=localhost:8080 --title=Logs2ELK \
        --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com

chown -R www-data:www-data /var/www/html
chmod -R g+w /var/www/html

update-ca-certificates
service cron start

FUNCTIONS=/var/www/html/wp-content/themes/twentytwentyfour/functions.php
if ! grep -q 'generate_random_error' $FUNCTIONS; then
  echo "add_action('init', 'generate_random_error');" >>$FUNCTIONS
fi

exec "$@"
