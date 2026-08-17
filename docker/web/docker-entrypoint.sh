#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    if [ "$APP_ENV" = "dev" ]; then
        if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
            composer install --no-cache --prefer-dist --no-progress --no-interaction
        fi

        # AssetMapper serves whatever is in public/assets/ in preference to the live files, so the previous run's
        # compiled output has to go. Nothing recreates it here: in development the asset map is served on demand, and
        # `asset-map:compile` belongs to the production build.
        rm -rf public/assets/

        # One watcher per compiled language. Sass alone leaves TypeScript compiled once and never again, which reads
        # as a Stimulus controller whose changes do not take until the container is restarted.
        php bin/console sass:build --watch > /dev/stdout 2>&1 &
        php bin/console typescript:build --watch > /dev/stdout 2>&1 &
        php bin/console importmap:install
    fi

    php bin/console -V

    if [ -n "$DATABASE_DSN" ]; then
        echo 'Waiting for database to be ready...'
        ATTEMPTS_LEFT_TO_REACH_DATABASE=60
        until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
            if [ $? -eq 255 ]; then
                ATTEMPTS_LEFT_TO_REACH_DATABASE=0
                break
            fi
            sleep 1
            ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
            echo "Still waiting for database to be ready... $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
        done

        if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
            echo 'The database is not up or not reachable:'
            echo "$DATABASE_ERROR"
            exit 1
        fi

        echo 'The database is now ready and reachable'

        # ReportDB's set cannot be expressed in the bundle's single configuration, so it is passed explicitly.
        if find ./migrations/database -iname '*.php' -print -quit | grep --quiet .; then
            php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
        fi

        if find ./migrations/report -iname '*.php' -print -quit | grep --quiet .; then
            php bin/console doctrine:migrations:migrate \
                --no-interaction \
                --all-or-nothing \
                --em=report \
                --configuration=migrations/report.yaml
        fi
    fi

    echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
