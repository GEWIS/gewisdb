.PHONY: help runprod runprodtest rundev stop exec update updatecomposer updatedocker getvendordir phpstan phpstanpr phpcs phpcbf phpcbfall phpcsfix checkcomposer replenish build buildprod builddev buildweb buildwebprod buildwebdev buildpgadmin preparelistmonk preparemailman migrate migrate-to migration-down migration-up migration-diff seed goldens goldens-verify goldens-freeze goldens-restore entity-schema translations runtest runcoverage stripewebhooksecret

help:
		@echo "Makefile commands:"
		@echo "runprod"
		@echo "rundev"
		@echo "updatecomposer"
		@echo "updatedocker"
		@echo "getvendordir"
		@echo "phpstan"
		@echo "phpcs"
		@echo "phpcbf"
		@echo "phpcsfix"
		@echo "replenish"
		@echo "translations"
		@echo "build"
		@echo "buildprod"
		@echo "builddev"
		@echo "update = updatecomposer updatedocker"

.DEFAULT_GOAL := rundev

LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)
SHELL := /bin/bash

CONSOLE := docker compose exec -u www-data -T web bin/console

runprod:
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

runprodtest: buildprod
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

rundev: builddev
		@docker compose up -d --build --remove-orphans
		@make replenish

migrate: replenish
		@docker compose exec -u www-data -it web bin/console doctrine:migrations:migrate --em=default
		@docker compose exec -u www-data -it web bin/console doctrine:migrations:migrate --em=report

migrate-to:
		@docker compose exec -u www-data web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:migrate $$migrations --em=$$alias'

migration-diff: replenish
		@set -e; \
		for manager in default report; do \
			echo "Generating migrations for $$manager..."; \
			$(CONSOLE) doctrine:migrations:diff --allow-empty-diff --em=$$manager; \
		done
		@docker cp "$$(docker compose ps -q web)":/code/migrations ./

migration-up: replenish
		@docker compose exec -u www-data web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --up $$migrations --em=$$alias'

migration-down: replenish
		@docker compose exec -u www-data web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --down $$migrations --em=$$alias'

seed: replenish
		@$(CONSOLE) doctrine:fixtures:load --no-interaction
		@$(CONSOLE) report:generate:full
		@make preparemailman
		@docker compose exec mailman-web bash -c '(python3 ./manage.py createsuperuser --no-input 2>/dev/null || true)'
		@docker compose exec -u mailman mailman-core bash -c '(mailman create news@$$MAILMAN_DOMAIN; mailman create other@$$MAILMAN_DOMAIN; true) 2>/dev/null'
		@make preparelistmonk
		@$(CONSOLE) database:mailinglist:fetch

# goldens-verify and goldens-restore drop and recreate the public schema in both databases.
goldens: replenish
		@bash scripts/goldens/capture.sh

goldens-verify: replenish
		@bash scripts/goldens/verify.sh

goldens-freeze:
		@bash scripts/goldens/freeze-input.sh

goldens-restore:
		@bash scripts/goldens/restore-input.sh

# Runs on the host: the script needs bash, and the web image is Alpine.
entity-schema:
		@DOCTRINE_DEFAULT_HOST=127.0.0.1 DOCTRINE_REPORT_HOST=127.0.0.1 bash scripts/goldens/entity-schema-check.sh

exec:
		docker compose exec -u www-data -it web $(cmd)

stop:
		@docker compose down --remove-orphans

runtest:
		@vendor/bin/phpunit --stop-on-error --stop-on-failure

runcoverage:
		@XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./coverage

getvendordir:
		@rm -Rf ./vendor
		@docker compose cp web:/code/vendor ./vendor
		@docker compose cp web:/code/composer.json ./
		@docker compose cp web:/code/composer.lock ./

replenish:
		@docker compose cp ./public web:/code
		@docker compose cp ./src web:/code
		@docker compose cp ./config web:/code
		@docker compose cp ./templates web:/code
		@docker compose exec -u root web chown -R root:root /code/public /code/src /code/config /code/templates
		@docker compose exec -u root web chown -R www-data:www-data /code/public/data
		@docker compose exec -u root web composer dump-autoload --dev
		@$(CONSOLE) cache:clear

# --no-fill leaves new entries with an empty <target/> in BOTH locales; every one has to be filled before the change
# is done.
translations: replenish
		@$(CONSOLE) translation:extract en --format=xlf --sort=asc --no-fill --force --clean
		@$(CONSOLE) translation:extract nl --format=xlf --sort=asc --no-fill --force --clean

update: updatecomposer updatedocker

phpstan:
		@vendor/bin/phpstan analyse --memory-limit 1G

phpstanpr:
		@git fetch --all
		@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/main
		@git checkout --detach temp-phpstanpr
		@vendor/bin/phpstan analyse --generate-baseline phpstan/phpstan-baseline-pr.neon --memory-limit 1G --no-progress
		@git checkout -- phpstan/phpstan-baseline.neon
		@git checkout -
		@vendor/bin/phpstan analyse --memory-limit 1G --no-progress

phpcs:
		@vendor/bin/phpcs -p

phpcbf:
		@vendor/bin/phpcbf -p --filter=GitModified

phpcbfall:
		@vendor/bin/phpcbf -p

phpcsfix:
		@vendor/bin/php-cs-fixer fix --format=txt --verbose

checkcomposer:
		@XDEBUG_MODE=off vendor/bin/composer-require-checker check composer.json

updatecomposer:
		@docker cp ./composer.json $(shell docker compose ps -q web):/code/composer.json
		@docker compose exec web composer update -W
		@docker cp $(shell docker compose ps -q web):/code/composer.lock ./composer.lock

updatedocker:
		@docker compose pull
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/db/gewisdb/web:production -f docker/web/production/Dockerfile .
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/db/gewisdb/web:development -f docker/web/development/Dockerfile .

build: buildweb

buildprod: buildwebprod

builddev: buildwebdev

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/db/gewisdb/web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/db/gewisdb/web:development -f docker/web/development/Dockerfile .

buildpgadmin:
		@docker compose build pgadmin

preparemailman:
		@docker compose cp ./docker/mailman/settings_local.py mailman-web:/opt/mailman-web/settings_local.py
		@docker compose restart mailman-web

preparelistmonk:
		@echo -n "Adding listmonk API user to database if not exists: "
		@docker compose exec postgresql sh -c 'psql -q -U $${POSTGRES_USER} -d $${POSTGRES_LISTMONK_DATABASE} -c "INSERT INTO public.users (\"username\", \"password_login\", \"password\", \"email\", \"name\", \"type\", \"user_role_id\", \"list_role_id\", \"status\") VALUES ('\''$${LISTMONK_API_USERNAME}'\'', false, '\''$${LISTMONK_API_PASSWORD}'\'', '\''$${LISTMONK_API_USERNAME}@api'\'', '\''Listmonk API User'\'', '\''api'\'', 1, null, '\''enabled'\'') ON CONFLICT (\"username\") DO UPDATE SET \"username\" = '\''$${LISTMONK_API_USERNAME}'\'', \"password\" = '\''$${LISTMONK_API_PASSWORD}'\'', \"user_role_id\" = 1;"'
		@if [ $$? -eq 0 ]; then echo "success"; else echo "failed"; fi
		@docker compose restart listmonk

stripewebhooksecret:
		@echo -n "Stripe webhook signing secret: "
		@docker compose exec stripe stripe listen --print-secret
