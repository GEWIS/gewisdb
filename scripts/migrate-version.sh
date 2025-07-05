#/bin/sh

# This script finds a verison given a partial version number
# If you put this directly in the makefile, replace $ with $$
set -e

. /code/scripts/migrate-alias.sh

read -rp "Give (partial, unique) version name (e.g. Database\Migrations\Version20241020224949 or 20241020)): " version

migrations=$(./orm migrations:list --no-interaction --no-ansi --object-manager doctrine.entitymanager.$alias | grep -F "$version" | awk "{print \$2}")
migrationcount=$(echo $migrations | wc -w)
[ "$migrationcount" == "1" ] || (echo "Found $migrationcount migrations, expecting exactly 1, exiting..." 1>&2; exit 1)

echo "Found migration $migrations" 1>&2
export migrations=$migrations
