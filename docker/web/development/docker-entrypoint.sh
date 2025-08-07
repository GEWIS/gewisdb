#!/bin/sh
printenv | sed 's/^\(.*\)$/export \1/g' | grep -E "^export (APP|CHECKER|DOCTRINE|SMTP)_" > ./config/bash.env
crond -b -L /proc/1/fd/1
su www-data -s /bin/sh -c "./orm orm:generate-proxies"
su www-data -s /bin/sh -c "EM_ALIAS=orm_report ./orm orm:generate-proxies"
php-fpm -F -O
