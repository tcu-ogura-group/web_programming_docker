FROM php:8.5-apache

# 公式 php:*-apache イメージは mod_php + DocumentRoot /var/www/html が設定済みで、
#   /etc/apache2/conf-enabled/docker-php.conf
#     <FilesMatch \.php$> SetHandler application/x-httpd-php
#     DirectoryIndex index.php index.html
# まで入っている。ここでやるのは
#   1. mysqli / pdo_mysql の追加
#   2. mod_userdir を有効化して /~user/ を使えるようにする
#   3. php.ini の開発用設定
# の 3 つだけ。

ENV TZ=Asia/Tokyo

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
