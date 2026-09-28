ARG PHP_IMAGE
FROM ${PHP_IMAGE}

# 公式 php-fpm イメージは既に
#   listen = 9000 / daemonize = no / ログは stderr / clear_env = no
# が設定済みなので、拡張モジュールと php.ini の上書きだけ行う。

ARG TZ
ENV TZ=${TZ}

RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql; \
    ln -snf "/usr/share/zoneinfo/$TZ" /etc/localtime; \
    echo "$TZ" > /etc/timezone

COPY conf.d/zz-ssp.ini /usr/local/etc/php/conf.d/zz-ssp.ini

# nginx と同じパスでソースを見る (compose 側で bind mount)
WORKDIR /var/www/html
