FROM mysql:8.4

ENV TZ=Asia/Tokyo

COPY conf.d/ /etc/mysql/conf.d/
COPY init/ /docker-entrypoint-initdb.d/
