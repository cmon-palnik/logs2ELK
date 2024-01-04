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

RUN echo "30 5 * * * www-data logs2elk logs2elk:manage >> /var/log/cron.log 2>&1" | crontab
# configure logrotate and add after rotate action, eg.
# RUN echo '/var/www/log/debug.log {\n' \
#    '    daily\n' \
#    '    rotate 7\n' \
#    '    compress\n' \
#    '    delaycompress\n' \
#    '    missingok\n' \
#    '    notifempty\n' \
#    '    create 0644 www-data www-data\n' \
#    '    postrotate\n' \
#    '        cat $1 | logs2elk logs2elk:parse\n' \
#    '    endscript\n' \
#    '}' > /etc/logrotate.d/wordpress

COPY wp-install.sh /usr/local/bin/
COPY fn-generate-random-error.php /var/www/html/
