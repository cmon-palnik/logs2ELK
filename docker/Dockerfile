FROM php:8.2-cli
ENV TZ=Europe/Warsaw
WORKDIR /app
RUN echo $TZ >/etc/timezone
RUN date
# @todo: WTFIT??
#RUN echo "date.timezone = "$TZ >>/php.ini-dev
#RUN echo "date.timezone = "$TZ >>/php.ini-prod
RUN apt-get update && apt-get install -y \
    unzip \
    libzip4 \
    libzip-dev \
    && docker-php-ext-install zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENTRYPOINT ["docker/entrypoint.sh"]
