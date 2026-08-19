.PHONY: help start up setuplocalenv startprod startprodtest runprod runprodtest rundev stop exec update updatecomposer updatedocker getvendordir phpstan phpstanpr phpcs phpcbf phpcbfall phpcsfix checkcomposer cc build buildprod builddev buildweb buildwebprod buildwebdev buildpgadmin preparelistmonk preparemailman migrate migrate-to migration-down migration-up migration-diff seed smoke translations runtest runcoverage stripewebhooksecret

## —— GEWISDB —————————————————————————————————————————————————————————————————
help: ## Outputs this help screen
		@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-24s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

.DEFAULT_GOAL := help

LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)
SHELL := /bin/bash

# The image runs as `nonroot`, whose uid matches the host user's, which is what owns the bind-mounted source. Running
# the console as `www-data` instead cannot write to it, which is what used to break `make seed` and `make translations`
# on a permission error. GEWISWEB does not override the user either.
# Both files are named so compose substitutes from either: later --env-file wins, which is how a value in
# `.env.local` overrides the committed default. Services that are not the application — the Stripe CLI, postgres,
# pgadmin — get their values this way, since they do not read Symfony's dotenv chain. GEWISWEB passes them the same
# way. `setuplocalenv` guarantees the second file exists.
COMPOSE := docker compose --env-file=.env --env-file=.env.local
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

# ReportDB's migrations are a second set, and the bundle only holds one: without --configuration the ledger's
# migrations are applied to the report connection, which fills ReportDB with the ledger's tables.
REPORT_MIGRATIONS := --em=report --configuration=migrations/report.yaml

migrate: ## Run the migrations on both entity managers
		@$(COMPOSE) exec -it web bin/console doctrine:migrations:migrate --em=default
		@$(COMPOSE) exec -it web bin/console doctrine:migrations:migrate $(REPORT_MIGRATIONS)

migrate-to:
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:migrate $$migrations --em=$$alias'

migration-diff:
		@set -e; \
		echo "Generating migrations for default..."; \
		$(CONSOLE) doctrine:migrations:diff --allow-empty-diff --em=default; \
		echo "Generating migrations for report..."; \
		$(CONSOLE) doctrine:migrations:diff --allow-empty-diff $(REPORT_MIGRATIONS);
		@docker cp "$$(docker compose ps -q web)":/app/migrations ./

migration-up:
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --up $$migrations --em=$$alias'

migration-down:
		@$(COMPOSE) exec web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --down $$migrations --em=$$alias'

# The lists the fixtures seed. Both servers are given the same three, because the fixtures bind each list to the one
# of its own name on either side; a list here that neither server has would leave a synchronisation that can never
# succeed. Listmonk identifies a list by number rather than by name, so the ids are pinned to match the fixtures.
SEEDED_LISTS := announcements activities vacancies

seed: ## Load fixtures, generate the report database, and prepare Mailman and Listmonk
		@$(CONSOLE) doctrine:fixtures:load --no-interaction
		# Loading the fixtures empties the ledger and fills it again, and the members come back under new numbers.
		# ReportDB is a projection of what the ledger says, so it is rebuilt from nothing rather than written over:
		# left in place, its rows still point at the members of the run before, and the first list membership whose
		# address comes round again collides with the one recorded against a member who no longer exists.
		@$(CONSOLE) doctrine:schema:drop --force --full-database --em=report
		@$(CONSOLE) doctrine:migrations:migrate --no-interaction $(REPORT_MIGRATIONS)
		@$(CONSOLE) report:generate:full
		@make preparemailman
		@$(COMPOSE) exec mailman-web bash -c '(python3 ./manage.py createsuperuser --no-input 2>/dev/null || true)'
		# Removed and made again rather than left alone: the fixtures put every membership back as still to be
		# carried across, and Mailman answers 409 to a subscription it already has, which ends the whole run.
		@$(COMPOSE) exec -u mailman mailman-core bash -c 'for l in $(SEEDED_LISTS); do mailman remove $$l@$$MAILMAN_DOMAIN; mailman create $$l@$$MAILMAN_DOMAIN; done 2>/dev/null; true'
		@make preparelistmonk
		@$(CONSOLE) database:mailinglist:fetch


# Requests every GET route and reports the ones that fail; parameters come from whatever is in the database.
smoke: ## Request every GET route and report anything that does not answer
		@$(COMPOSE) exec -T web php scripts/smoke-routes.php

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

# The development stack bind-mounts the source, so there is nothing to copy in; the cache is what goes stale. The
# worker holds the compiled container and the asset manifest, so it is restarted with it, as GEWISWEB does. In
# development the file watcher restarts it on a change of its own, which is why nothing here depends on this.
cc: ## Clear the cache and restart the worker, which holds the compiled container and the asset manifest
		@$(CONSOLE) cache:clear
		@$(COMPOSE) restart web

# --no-fill leaves new entries with an empty <target/> in BOTH locales, and an empty target is used AS the
# translation, so the interface renders blank until every one is filled. Fill them before committing.
#
# --clean removes units the extractor no longer finds. It only finds a `new TranslatableMessage('...')` where the
# literal is the argument, so an enum must build its label as `match ($this) { self::X => new TranslatableMessage(...) }`
# and never as `new TranslatableMessage(match ($this) { self::X => '...' })`. The second form is invisible here and
# --clean deletes its translations.
translations: ## Extract translatable strings into the XLIFF files
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
		@echo -n "Seeding the lists Listmonk is bound to: "
		@$(COMPOSE) exec postgresql sh -c 'psql -q -U $${POSTGRES_USER} -d $${POSTGRES_LISTMONK_DATABASE} -c "INSERT INTO public.lists (\"id\", \"uuid\", \"name\", \"type\", \"optin\") VALUES (1, '\''a0000000-0000-4000-8000-000000000001'\'', '\''Announcements'\'', '\''private'\'', '\''single'\''), (2, '\''a0000000-0000-4000-8000-000000000002'\'', '\''Activities'\'', '\''public'\'', '\''single'\''), (3, '\''a0000000-0000-4000-8000-000000000003'\'', '\''Vacancies'\'', '\''public'\'', '\''single'\'') ON CONFLICT (\"id\") DO UPDATE SET \"uuid\" = EXCLUDED.\"uuid\", \"name\" = EXCLUDED.\"name\", \"type\" = EXCLUDED.\"type\", \"optin\" = EXCLUDED.\"optin\"; DELETE FROM public.subscriber_lists WHERE \"list_id\" IN (1, 2, 3); SELECT setval('\''lists_id_seq'\'', (SELECT max(\"id\") FROM public.lists));"' > /dev/null
		@if [ $$? -eq 0 ]; then echo "success"; else echo "failed"; fi
		@$(COMPOSE) restart listmonk

stripewebhooksecret:
		@echo -n "Stripe webhook signing secret: "
		@$(COMPOSE) exec stripe stripe listen --print-secret
