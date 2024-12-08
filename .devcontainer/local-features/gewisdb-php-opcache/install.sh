set -e

if [ "$(id -u)" -ne 0 ]; then
    echo -e 'Script must be run as root. Use sudo, su, or add "USER root" to your Dockerfile before running this script.'
    exit 1
fi

cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
echo "zend_extension=opcache" | tee /usr/local/etc/php/conf.d/gewisdb-php-opcache.ini