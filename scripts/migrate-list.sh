#/bin/sh

# This script prints the available migrations for a specific alias
set -e

. /code/scripts/migrate-alias.sh

./orm migrations:list --no-interaction --object-manager doctrine.entitymanager.$alias

export alias=$alias
export migrations=$migrations
