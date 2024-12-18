set -e

if [ "$(id -u)" -ne 0 ]; then
    echo -e 'Script must be run as root. Use sudo, su, or add "USER root" to your Dockerfile before running this script.'
    exit 1
fi

export PHP_INI_DIR="/usr/local/etc/php"

apt-get update && apt-get -y install --no-install-recommends libicu-dev
docker-php-ext-install intl
#echo "extension=php_intl" | tee /usr/local/etc/php/conf.d/gewisdb-php-intl.ini
apt-get clean -y && rm -rf /var/lib/apt/lists/*