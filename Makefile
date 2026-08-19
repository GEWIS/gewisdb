.PHONY: help start up setuplocalenv startprod startprodtest runprod runprodtest rundev stop exec update updatecomposer updatedocker getvendordir phpstan phpstanpr phpcs phpcbf phpcbfall phpcsfix checkcomposer replenish build buildprod builddev buildweb buildwebprod buildwebdev buildpgadmin preparelistmonk preparemailman migrate migrate-to migration-down migration-up migration-diff seed goldens goldens-verify goldens-freeze goldens-restore entity-schema smoke translations runtest runcoverage stripewebhooksecret

## —— GEWISDB —————————————————————————————————————————————————————————————————
help: ## Outputs this help screen
		@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-24s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

.DEFAULT_GOAL := help

LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)
SHELL := /bin/bash

# The image runs as `nonroot`, whose uid matches the host user's, which is what owns the bind-mounted source. Running
# the console as `www-data` instead cannot write to it, which is what used to break `make seed` and `make translations`
# on a permission error. GEWISWEB does not override the user either.
# Compose reads `.env` by itself. It does NOT merge several --env-file flags (v5 takes the first and ignores the
# rest), so `.env.local` is layered by Symfony inside the container rather than by compose. A name listed under the
# web service's `environment:` is injected by compose and therefore cannot be overridden from `.env.local`.
COMPOSE := docker compose
CONSOLE := $(COMPOSE) exec -T web bin/console

start: builddev up ## Build and start the development stack

up: setuplocalenv ## Start the development stack
		@# var/ is created as the host user first; left to compose it belongs to root and the non-root container
		@# cannot write var/cache.
		@mkdir -p var
		@$(COMPOSE) up --detach --remove-orphans

setuplocalenv:
		@if [ ! -f .env.local ]; then \
			cp .env.local.dist .env.local; \
			echo ".env.local created from .env.local.dist; alter it to your needs"; \
		fi

startprod: ## Start the production stack
		@$(COMPOSE) -f docker-compose.yml up -d --force-recreate --remove-orphans

startprodtest: buildprod ## Build and start the production stack from local sources
		@$(COMPOSE) -f docker-compose.yml up -d --force-recreate --remove-orphans

# The names this project used before it followed GEWISWEB's; kept so anything that calls them still works.
rundev: start
runprod: startprod
runprodtest: startprodtest

migrate: replenish ## Run the migrations on both entity managers
		@$(COMPOSE) exec -it web bin/console doctrine:migrations:migrate --em=default
		@$(COMPOSE) exec -it web bin/console doctrine:migrations:migrate --em=report

migrate-to:
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:migrate $$migrations --em=$$alias'

migration-diff: replenish
		@set -e; \
		for manager in default report; do \
			echo "Generating migrations for $$manager..."; \
			$(CONSOLE) doctrine:migrations:diff --allow-empty-diff --em=$$manager; \
		done
		@docker cp "$$(docker compose ps -q web)":/app/migrations ./

migration-up: replenish
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --up $$migrations --em=$$alias'

migration-down: replenish
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --down $$migrations --em=$$alias'

seed: replenish ## Load fixtures, generate the report database, and prepare Mailman and Listmonk
		@$(CONSOLE) doctrine:fixtures:load --no-interaction
		@$(CONSOLE) report:generate:full
		@make preparemailman
		@$(COMPOSE) exec mailman-web bash -c '(python3 ./manage.py createsuperuser --no-input 2>/dev/null || true)'
		@$(COMPOSE) exec -u mailman mailman-core bash -c '(mailman create news@$$MAILMAN_DOMAIN; mailman create other@$$MAILMAN_DOMAIN; true) 2>/dev/null'
		@make preparelistmonk
		@$(CONSOLE) database:mailinglist:fetch

# goldens-verify and goldens-restore drop and recreate the public schema in both databases.
goldens: replenish ## Capture the behavioural goldens
		@bash scripts/goldens/capture.sh

goldens-verify: replenish ## Check current behaviour against the goldens
		@bash scripts/goldens/verify.sh

goldens-freeze:
		@bash scripts/goldens/freeze-input.sh

goldens-restore:
		@bash scripts/goldens/restore-input.sh

# Requests every GET route and reports the ones that fail; parameters come from whatever is in the database.
smoke: ## Request every GET route and report anything that does not answer
		@$(COMPOSE) exec -T web php scripts/smoke-routes.php

# Runs on the host, against the database the containers expose.
entity-schema:
		@DOCTRINE_DEFAULT_HOST=127.0.0.1 DOCTRINE_REPORT_HOST=127.0.0.1 bash scripts/goldens/entity-schema-check.sh

exec: ## Open a shell in the web container
		docker compose exec -it web $(cmd)

stop: ## Stop the stack
		@$(COMPOSE) down --remove-orphans

runtest:
		@vendor/bin/phpunit --stop-on-error --stop-on-failure

runcoverage:
		@XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./coverage

getvendordir:
		@rm -Rf ./vendor
		@$(COMPOSE) cp web:/app/vendor ./vendor
		@$(COMPOSE) cp web:/app/composer.json ./
		@$(COMPOSE) cp web:/app/composer.lock ./

# The development stack bind-mounts the source, so there is nothing to copy in; the cache is what goes stale.
replenish: ## Clear the cache in the container
		@$(CONSOLE) cache:clear

# --no-fill leaves new entries with an empty <target/> in BOTH locales, and an empty target is used AS the
# translation, so the interface renders blank until every one is filled. Fill them before committing.
#
# --clean removes units the extractor no longer finds. It only finds a `new TranslatableMessage('...')` where the
# literal is the argument, so an enum must build its label as `match ($this) { self::X => new TranslatableMessage(...) }`
# and never as `new TranslatableMessage(match ($this) { self::X => '...' })`. The second form is invisible here and
# --clean deletes its translations.
translations: replenish ## Extract translatable strings into the XLIFF files
		@$(CONSOLE) translation:extract en --format=xlf --sort=asc --no-fill --force --clean
		@$(CONSOLE) translation:extract nl --format=xlf --sort=asc --no-fill --force --clean

update: updatecomposer updatedocker

phpstan: ## Run static analysis
		@vendor/bin/phpstan analyse --memory-limit 1G

phpstanpr:
		@git fetch --all
		@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/main
		@git checkout --detach temp-phpstanpr
		@vendor/bin/phpstan analyse --generate-baseline phpstan/phpstan-baseline-pr.neon --memory-limit 1G --no-progress
		@git checkout -- phpstan/phpstan-baseline.neon
		@git checkout -
		@vendor/bin/phpstan analyse --memory-limit 1G --no-progress

phpcs: ## Check the coding standard
		@vendor/bin/phpcs -p

phpcbf: ## Fix what the coding standard can fix
		@vendor/bin/phpcbf -p --filter=GitModified

phpcbfall:
		@vendor/bin/phpcbf -p

phpcsfix:
		@vendor/bin/php-cs-fixer fix --format=txt --verbose

checkcomposer:
		@XDEBUG_MODE=off vendor/bin/composer-require-checker check composer.json

updatecomposer:
		@docker cp ./composer.json $(shell docker compose ps -q web):/app/composer.json
		@$(COMPOSE) exec web composer update -W
		@docker cp $(shell docker compose ps -q web):/app/composer.lock ./composer.lock

updatedocker:
		@$(COMPOSE) pull
		@docker build --pull --no-cache --target gewisdb_web_production -t abc.docker-registry.gewis.nl/db/gewisdb/web:production .
		@docker build --pull --no-cache --target gewisdb_web_development -t abc.docker-registry.gewis.nl/db/gewisdb/web:development .

build: buildweb

buildprod: buildwebprod

builddev: buildwebdev

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --target gewisdb_web_production -t abc.docker-registry.gewis.nl/db/gewisdb/web:production .

buildwebdev:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --target gewisdb_web_development -t abc.docker-registry.gewis.nl/db/gewisdb/web:development .

buildpgadmin:
		@$(COMPOSE) build pgadmin

preparemailman:
		@$(COMPOSE) cp ./docker/mailman/settings_local.py mailman-web:/opt/mailman-web/settings_local.py
		@$(COMPOSE) restart mailman-web

preparelistmonk:
		@echo -n "Adding listmonk API user to database if not exists: "
		@$(COMPOSE) exec postgresql sh -c 'psql -q -U $${POSTGRES_USER} -d $${POSTGRES_LISTMONK_DATABASE} -c "INSERT INTO public.users (\"username\", \"password_login\", \"password\", \"email\", \"name\", \"type\", \"user_role_id\", \"list_role_id\", \"status\") VALUES ('\''$${LISTMONK_API_USERNAME}'\'', false, '\''$${LISTMONK_API_PASSWORD}'\'', '\''$${LISTMONK_API_USERNAME}@api'\'', '\''Listmonk API User'\'', '\''api'\'', 1, null, '\''enabled'\'') ON CONFLICT (\"username\") DO UPDATE SET \"username\" = '\''$${LISTMONK_API_USERNAME}'\'', \"password\" = '\''$${LISTMONK_API_PASSWORD}'\'', \"user_role_id\" = 1;"'
		@if [ $$? -eq 0 ]; then echo "success"; else echo "failed"; fi
		@$(COMPOSE) restart listmonk

stripewebhooksecret:
		@echo -n "Stripe webhook signing secret: "
		@$(COMPOSE) exec stripe stripe listen --print-secret
