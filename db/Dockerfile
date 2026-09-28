ARG MYSQL_IMAGE
FROM ${MYSQL_IMAGE}

ARG TZ
ENV TZ=${TZ}

COPY conf.d/ /etc/mysql/conf.d/
COPY init/ /docker-entrypoint-initdb.d/
