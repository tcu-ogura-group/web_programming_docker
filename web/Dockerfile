ARG PHP_IMAGE
FROM ${PHP_IMAGE}

ARG TZ
ENV TZ=${TZ}

RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql; \
    a2enmod userdir; \
    ln -snf "/usr/share/zoneinfo/$TZ" /etc/localtime; \
    echo "$TZ" > /etc/timezone

# conf-enabled は mods-enabled より後に読まれるので、
# userdir.conf の UserDir をこちらで上書きできる (zz- で最後に読ませる)。
COPY apache/zz-ssp.conf /etc/apache2/conf-available/zz-ssp.conf
RUN a2enconf zz-ssp

COPY php/zz-ssp.ini /usr/local/etc/php/conf.d/zz-ssp.ini

WORKDIR /var/www/html
