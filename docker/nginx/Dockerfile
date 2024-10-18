FROM nginx:stable-alpine AS gewisdb_nginx

RUN adduser -D -H -u 1000 -s /bin/sh www-data -G www-data

RUN apk add --no-cache --virtual .build-deps \
        tzdata \
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && apk del .build-deps

COPY --chown=www-data:www-data . /etc/nginx
COPY --chown=www-data:www-data nginx.conf /etc/nginx/nginx.conf.template

VOLUME ["/etc/nginx/logs"]

CMD ["/bin/sh" , "-c" , "envsubst '${NGINX_REQUIRE_AUTH} ${NGINX_REQUIRE_AUTH}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'"]
