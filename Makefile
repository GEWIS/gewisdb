# Executables (local)
DOCKER = docker
# In dev we explicitly pass --env-file twice so compose substitution reads both `.env` (committed defaults) and
# `.env.local` (developer overrides): later --env-file wins. Services that are not the application — the Stripe CLI,
# postgres, pgadmin — get their values this way, since they do not read Symfony's dotenv chain. `setuplocalenv`
# guarantees the second file exists.
DOCKER_COMP      = $(DOCKER) compose --env-file=.env --env-file=.env.local
DOCKER_COMP_PROD = $(DOCKER_COMP) -f compose.yaml

# Docker containers
# The image runs as `nonroot`, whose uid matches the host user's, which is what owns the bind-mounted source.
# Running the console as `www-data` instead cannot write to it, which is what used to break `seed` and `translations`
# on a permission error.
PHP_CONT = $(DOCKER_COMP) exec web

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
# -T rather than $(PHP) bin/console: the migration workflow runs these targets on a runner that has no TTY.
SYMFONY  = $(DOCKER_COMP) exec -T web bin/console
# The same console against the test environment, which `when@test` points at the `_test` databases.
SYMFONY_TEST = $(DOCKER_COMP) exec -T -e APP_ENV=test web bin/console

# Misc
.DEFAULT_GOAL   = help
.PHONY          : help seed translations igor lint lint-fix lint-fix-all lint-twig phpstan phpstan-pr \
                  test test-coverage test-prepare build builddev buildprod buildweb buildwebdev buildwebprod \
                  buildpgadmin setuplocalenv up start startprod startprodtest stop logs bash exec composer sf cc \
                  update updatecomposer updatedocker getvendordir migrate migrate-to migration-up migration-down \
                  migration-diff preparemailman preparelistmonk stripewebhooksecret rundev runprod runprodtest \
                  runtest runcoverage phpcs phpcbf phpcbfall
LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD 2>/dev/null || echo abcabcabc)
HOST_UID        := $(shell id -u)
HOST_GID        := $(shell id -g)
SHELL           := /bin/bash

## —— GEWISDB —————————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

# The lists the fixtures seed. Both servers are given the same three, because the fixtures bind each list to the one
# of its own name on either side; a list here that neither server has would leave a synchronisation that can never
# succeed. Listmonk identifies a list by number rather than by name, so the ids are pinned to match the fixtures.
SEEDED_LISTS := announcements activities vacancies

seed: ## Load fixtures, generate the report database, and prepare Mailman and Listmonk (run after `make start`)
	@$(SYMFONY) doctrine:fixtures:load --no-interaction
	# Loading the fixtures empties the ledger and fills it again, and the members come back under new numbers.
	# ReportDB is a projection of what the ledger says, so it is rebuilt from nothing rather than written over:
	# left in place, its rows still point at the members of the run before, and the first list membership whose
	# address comes round again collides with the one recorded against a member who no longer exists.
	@$(SYMFONY) doctrine:schema:drop --force --full-database --em=report
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction $(REPORT_MIGRATIONS)
	@$(SYMFONY) report:generate:full
	@$(MAKE) preparemailman
	@$(DOCKER_COMP) exec mailman-web bash -c '(python3 ./manage.py createsuperuser --no-input 2>/dev/null || true)'
	# Removed and made again rather than left alone: the fixtures put every membership back as still to be
	# carried across, and Mailman answers 409 to a subscription it already has, which ends the whole run.
	@$(DOCKER_COMP) exec -u mailman mailman-core bash -c 'for l in $(SEEDED_LISTS); do mailman remove $$l@$$MAILMAN_DOMAIN; mailman create $$l@$$MAILMAN_DOMAIN; done 2>/dev/null; true'
	@$(MAKE) preparelistmonk
	@$(SYMFONY) database:mailinglist:fetch

# --no-fill leaves new entries with an empty <target/> in BOTH locales, and an empty target is used AS the
# translation, so the interface renders blank until every one is filled. Fill them before committing.
#
# --clean removes units the extractor no longer finds. It only finds a `new TranslatableMessage('...')` where the
# literal is the argument, so an enum must build its label as `match ($this) { self::X => new TranslatableMessage(...) }`
# and never as `new TranslatableMessage(match ($this) { self::X => '...' })`. The second form is invisible here and
# --clean deletes its translations.
translations: ## Extract untranslated text to the XLIFF files (also removes entries no longer referenced in source)
	@$(SYMFONY) translation:extract en --format=xlf --sort=asc --no-fill --force --clean
	@$(SYMFONY) translation:extract nl --format=xlf --sort=asc --no-fill --force --clean

igor: ## Run Igor (static linter to validate Symfony project for the persistent memory model of FrankenPHP)
	@$(PHP) ./vendor/bin/igor-php .

lint: ## Linter using PHP_CodeSniffer
	@$(PHP) ./vendor/bin/phpcs -p

lint-fix: ## Lint fix using phpcbf, limited to what git reports as modified
	@$(PHP) ./vendor/bin/phpcbf -p --filter=GitModified

lint-fix-all: ## Lint fix using phpcbf, over the whole tree
	@$(PHP) ./vendor/bin/phpcbf -p

lint-twig: ## Validate Twig templates
	@$(SYMFONY) lint:twig templates

phpstan: ## Static analysis using PHPStan
	@$(PHP) ./vendor/bin/phpstan analyse -c phpstan.dist.neon --memory-limit 1G

phpstan-pr: ## Regenerate the baseline against main, then analyse this branch against it
	@git fetch --all
	@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/main
	@git checkout --detach temp-phpstanpr
	@$(PHP) ./vendor/bin/phpstan analyse -c phpstan.dist.neon --generate-baseline phpstan-baseline-pr.neon --memory-limit 1G --no-progress
	@git checkout -- phpstan-baseline.neon
	@git checkout -
	@$(PHP) ./vendor/bin/phpstan analyse -c phpstan.dist.neon --memory-limit 1G --no-progress

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -T -e APP_ENV=test web bin/phpunit $(c)

test-coverage: ## Run the tests and write an HTML coverage report to ./coverage
	@$(DOCKER_COMP) exec -T -e APP_ENV=test -e XDEBUG_MODE=coverage web bin/phpunit --coverage-html ./coverage

# The schemas are built from the mapping metadata rather than by the migrations: what the tests run against is what
# the entities say, and the migrations are checked separately by the migration workflow. ReportDB is filled by
# replaying the ledger, exactly as `seed` does, because its listeners stand down while fixtures load.
test-prepare: ## Prepare the isolated test databases: (re)build both schemas and load the seed. Run once, and again after a schema or fixture change (the tests roll back their own writes, so the seed survives a run)
	@$(SYMFONY_TEST) doctrine:database:create --if-not-exists
	@$(SYMFONY_TEST) doctrine:database:create --if-not-exists --connection=report
	@$(SYMFONY_TEST) doctrine:schema:drop --force --full-database
	@$(SYMFONY_TEST) doctrine:schema:create
	@$(SYMFONY_TEST) doctrine:schema:drop --force --full-database --em=report
	@$(SYMFONY_TEST) doctrine:schema:create --em=report
	@$(SYMFONY_TEST) doctrine:fixtures:load --no-interaction
	@$(SYMFONY_TEST) report:generate:full

## —— Docker ———————————————————————————————————————————————————————————————————
builddev: buildwebdev ## Builds the development Docker images

buildprod: buildwebprod ## Builds the production Docker images

build: buildweb

buildweb: buildwebprod buildwebdev

# USER_UID/USER_GID are passed so the container user owns the bind-mounted source on a host where the developer is
# not uid 1000; without them the image's default 1000 cannot write var/ or the sources.
buildwebdev:
	@$(DOCKER) build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --build-arg USER_UID="$(HOST_UID)" --build-arg USER_GID="$(HOST_GID)" --target gewisdb_web_development -t abc.docker-registry.gewis.nl/db/gewisdb/web:development .

buildwebprod:
	@$(DOCKER) build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --target gewisdb_web_production -t abc.docker-registry.gewis.nl/db/gewisdb/web:production .

buildpgadmin:
	@$(DOCKER_COMP) build pgadmin

setuplocalenv:
	@if [ ! -f .env.local ]; then \
		cp .env.local.dist .env.local; \
		echo ".env.local created from .env.local.dist; alter it to your needs"; \
	fi

up: setuplocalenv ## Start the development Docker images in detached mode (no logs)
	@# Create var/ as the host user first; otherwise Docker creates the bind-mount source as root and the non-root
	@# container cannot write var/cache.
	@mkdir -p var
	@$(DOCKER_COMP) up --detach --remove-orphans

start: builddev up ## Build and start the development Docker containers

startprod: ## Start the production Docker images in detached mode (no logs)
	@$(DOCKER_COMP_PROD) up -d --force-recreate --remove-orphans

startprodtest: buildprod startprod ## Build the production images from local sources and start them

stop: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

bash: ## Connect to the FrankenPHP container
	@$(PHP_CONT) bash

exec: ## Run a command in the FrankenPHP container, example: make exec cmd="ls -la"
	@$(eval cmd ?=)
	@$(PHP_CONT) $(cmd)

# The development stack bind-mounts the source, so there is nothing to copy in; the cache is what goes stale. The
# worker holds the compiled container and the asset manifest, so it is restarted with it, as GEWISWEB does. In
# development the file watcher restarts it on a change of its own, which is why nothing here depends on this.
cc: ## Clear the cache and restart the worker, which holds the compiled container and the asset manifest
	@$(SYMFONY) cache:clear
	@$(DOCKER_COMP) restart web

update: updatecomposer updatedocker

updatedocker:
	@$(DOCKER_COMP) pull
	@$(DOCKER) build --pull --no-cache --target gewisdb_web_production -t abc.docker-registry.gewis.nl/db/gewisdb/web:production .
	@$(DOCKER) build --pull --no-cache --build-arg USER_UID="$(HOST_UID)" --build-arg USER_GID="$(HOST_GID)" --target gewisdb_web_development -t abc.docker-registry.gewis.nl/db/gewisdb/web:development .

# vendor/ lives in the image rather than the bind mount, so it is copied out for the IDE to index.
getvendordir: ## Copy vendor/ and the composer files out of the container, for the IDE to index
	@rm -Rf ./vendor
	@$(DOCKER_COMP) cp web:/app/vendor ./vendor
	@$(DOCKER_COMP) cp web:/app/composer.json ./
	@$(DOCKER_COMP) cp web:/app/composer.lock ./

## —— Composer —————————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

updatecomposer:
	@$(DOCKER) cp ./composer.json $(shell $(DOCKER_COMP) ps -q web):/app/composer.json
	@$(COMPOSER) update -W
	@$(DOCKER) cp $(shell $(DOCKER_COMP) ps -q web):/app/composer.lock ./composer.lock

## —— Symfony ——————————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

## —— Doctrine —————————————————————————————————————————————————————————————————
# ReportDB's migrations are a second set, and the bundle only holds one: without --configuration the ledger's
# migrations are applied to the report connection, which fills ReportDB with the ledger's tables.
REPORT_MIGRATIONS := --em=report --configuration=migrations/report.yaml

migrate: ## Run the migrations on both entity managers
	@$(SYMFONY) doctrine:migrations:migrate --em=default
	@$(SYMFONY) doctrine:migrations:migrate $(REPORT_MIGRATIONS)

migrate-to: ## Migrate one entity manager to a given version
	@$(DOCKER_COMP) exec -T web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:migrate $$migrations --em=$$alias'

migration-diff: ## Generate migrations for both entity managers from the current entities
	@set -e; \
	echo "Generating migrations for default..."; \
	$(SYMFONY) doctrine:migrations:diff --allow-empty-diff --em=default; \
	echo "Generating migrations for report..."; \
	$(SYMFONY) doctrine:migrations:diff --allow-empty-diff $(REPORT_MIGRATIONS);
	@$(DOCKER) cp "$$($(DOCKER_COMP) ps -q web)":/app/migrations ./

migration-up: ## Execute a single migration upwards
	@$(DOCKER_COMP) exec -T web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --up $$migrations --em=$$alias'

migration-down: ## Execute a single migration downwards
	@$(DOCKER_COMP) exec -T web sh -c '. ./scripts/migrate-version.sh && bin/console doctrine:migrations:execute --down $$migrations --em=$$alias'

## —— External services ————————————————————————————————————————————————————————
preparemailman:
	@$(DOCKER_COMP) cp ./docker/mailman/settings_local.py mailman-web:/opt/mailman-web/settings_local.py
	@$(DOCKER_COMP) restart mailman-web

preparelistmonk:
	@echo -n "Adding listmonk API user to database if not exists: "
	@$(DOCKER_COMP) exec postgresql sh -c 'psql -q -U $${POSTGRES_USER} -d $${POSTGRES_LISTMONK_DATABASE} -c "INSERT INTO public.users (\"username\", \"password_login\", \"password\", \"email\", \"name\", \"type\", \"user_role_id\", \"list_role_id\", \"status\") VALUES ('\''$${LISTMONK_API_USERNAME}'\'', false, '\''$${LISTMONK_API_PASSWORD}'\'', '\''$${LISTMONK_API_USERNAME}@api'\'', '\''Listmonk API User'\'', '\''api'\'', 1, null, '\''enabled'\'') ON CONFLICT (\"username\") DO UPDATE SET \"username\" = '\''$${LISTMONK_API_USERNAME}'\'', \"password\" = '\''$${LISTMONK_API_PASSWORD}'\'', \"user_role_id\" = 1;"'
	@if [ $$? -eq 0 ]; then echo "success"; else echo "failed"; fi
	@echo -n "Seeding the lists Listmonk is bound to: "
	@$(DOCKER_COMP) exec postgresql sh -c 'psql -q -U $${POSTGRES_USER} -d $${POSTGRES_LISTMONK_DATABASE} -c "INSERT INTO public.lists (\"id\", \"uuid\", \"name\", \"type\", \"optin\") VALUES (1, '\''a0000000-0000-4000-8000-000000000001'\'', '\''Announcements'\'', '\''private'\'', '\''single'\''), (2, '\''a0000000-0000-4000-8000-000000000002'\'', '\''Activities'\'', '\''public'\'', '\''single'\''), (3, '\''a0000000-0000-4000-8000-000000000003'\'', '\''Vacancies'\'', '\''public'\'', '\''single'\'') ON CONFLICT (\"id\") DO UPDATE SET \"uuid\" = EXCLUDED.\"uuid\", \"name\" = EXCLUDED.\"name\", \"type\" = EXCLUDED.\"type\", \"optin\" = EXCLUDED.\"optin\"; DELETE FROM public.subscriber_lists WHERE \"list_id\" IN (1, 2, 3); SELECT setval('\''lists_id_seq'\'', (SELECT max(\"id\") FROM public.lists));"' > /dev/null
	@if [ $$? -eq 0 ]; then echo "success"; else echo "failed"; fi
	@$(DOCKER_COMP) restart listmonk

stripewebhooksecret: ## Print the Stripe webhook signing secret the local CLI listener uses
	@echo -n "Stripe webhook signing secret: "
	@$(DOCKER_COMP) exec stripe stripe listen --print-secret

## —— Legacy aliases ———————————————————————————————————————————————————————————
# The names this project used before it followed GEWISWEB's; kept so anything that calls them still works.
rundev: start
runprod: startprod
runprodtest: startprodtest
runtest: test
runcoverage: test-coverage
phpcs: lint
phpcbf: lint-fix
phpcbfall: lint-fix-all
phpstanpr: phpstan-pr
