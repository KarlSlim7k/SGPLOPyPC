FROM phpmyadmin:5.2.2-apache

COPY docker-entrypoint-railway.sh /usr/local/bin/docker-entrypoint-railway.sh

RUN chmod +x /usr/local/bin/docker-entrypoint-railway.sh

CMD ["/usr/local/bin/docker-entrypoint-railway.sh"]
