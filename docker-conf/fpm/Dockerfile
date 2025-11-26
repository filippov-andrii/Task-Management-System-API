FROM php:8.3-fpm

RUN rm /etc/apt/preferences.d/no-debian-php \
&& apt-get update -y \
&& apt-get install -y git zip unzip gosu \
&& docker-php-ext-install pdo_mysql opcache

RUN pecl install xdebug

COPY www.conf /usr/local/etc/php-fpm.d/www.conf
COPY opcache.ini /usr/local/etc/php-fpm.d/opcache.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/docker-andrii-test-smake
COPY ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh
EXPOSE 9001

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["php-fpm"]