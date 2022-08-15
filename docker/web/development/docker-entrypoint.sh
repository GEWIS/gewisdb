#!/bin/sh
printenv | sed 's/^\(.*\)$/export \1/g' | grep -E "^export (APP|CHECKER|DOCTRINE|SMTP)_" > ./config/bash.env
crond -b
./orm orm:generate-proxies
EM_ALIAS=orm_report ./orm orm:generate-proxies
php-fpm -F -O
