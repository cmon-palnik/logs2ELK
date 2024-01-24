FROM wordpress:apache

RUN apt-get update && apt-get install -y less git cron nano mariadb-client

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

RUN set -eux; \
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"; \
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then \
        >&2 echo 'ERROR: Invalid Composer installer checksum. Exited.'; \
        rm composer-setup.php; \
        exit 1; \
    fi; \
    php composer-setup.php; \
    RESULT=$?; \
    rm composer-setup.php ;\
    if [ ! $RESULT -eq 0 ]; then \
        echo "Composer installation problem. Exited."; \
        exit 1; \
    fi; \
    mv /var/www/html/composer.phar /usr/local/bin/composer; \
    chmod +x /usr/local/bin/composer

RUN set -ex; \
    git clone https://github.com/cmon-palnik/logs2ELK.git ~/logs2ELK; \
    cd ~/logs2ELK && \
    bash install.sh prod

RUN set -ex; \
    COMMAND="www-data /usr/local/bin/logs2elk"; \
    echo "0 4 1,14 * * $COMMAND logs2elk:manage >/proc/1/fd/1 2>&1" | crontab; \
    echo "* * * * *  $COMMAND logs2elk:sysstat appsys prod wp >/proc/1/fd/1 2>&1" | crontab; \
    sed -i 's/error_log = \/dev\/stderr//g' /usr/local/etc/php/conf.d/error-logging.ini


COPY log.conf /etc/apache2/conf-enabled/
COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
COPY apache2-wordpress.sh /usr/local/bin/

ENTRYPOINT ["docker-entrypoint.sh", "apache2-wordpress.sh", "apache2-foreground"]
