#!/bin/bash
set -eux
cd /var/www/html/
THEME=/var/www/html/wp-content/themes/twentytwentyfour/

wp core is-installed --allow-root || wp core install --allow-root --url=localhost:8080 --title=Logs2ELK \
        --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com

if [ ! -e "$THEME/error_gen.php" ]; then
    cp /root/error_gen.php $THEME
fi
if ! grep -q 'error_gen.php' $THEME/functions.php; then
  echo "\$fle = __DIR__.'/error_gen.php'; if (file_exists(\$fle)) require_once \$fle;" >>$THEME/functions.php
fi

chown -R www-data:www-data /var/www/html
chmod -R g+w /var/www/html

update-ca-certificates
service cron start

exec "$@"
