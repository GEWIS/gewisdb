.PHONY: help runprod rundev runtest runcoverage update updatecomposer getvendordir phpstan phpcs phpcbf phpcsfix phpcsfixtypes replenish compilelang build buildprod builddev update preparemailman migrate migrate-to migration-down migration-up migration-diff

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
		@echo "phpcsfixtypes"
		@echo "replenish"
		@echo "compilelang"
		@echo "build"
		@echo "buildprod"
		@echo "builddev"
		@echo "update = updatecomposer"

.DEFAULT_GOAL := rundev

MODULE_DIR := ./module
LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)
SHELL := /bin/bash
TRANSLATIONS_DIR := $(MODULE_DIR)/Application/language/

runprod:
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

runprodtest: buildprod
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

rundev: builddev
		@docker compose up -d --build --remove-orphans
		@make replenish
		@docker compose exec web rm -rf data/cache/module-config-cache.application.config.cache.php

migrate: replenish
		@docker compose exec -it web ./orm migrations:migrate --object-manager doctrine.entitymanager.orm_default
		@docker compose exec -it web ./orm migrations:migrate --object-manager doctrine.entitymanager.orm_report

migrate-to:
		@docker compose exec web sh -c '. ./scripts/migrate-version.sh && ./orm migrations:migrate $$migrations --object-manager doctrine.entitymanager.$$alias'

migration-list: replenish
		@docker compose exec -T web ./orm migrations:list --object-manager doctrine.entitymanager.orm_default
		@docker compose exec -T web ./orm migrations:list --object-manager doctrine.entitymanager.orm_report

migration-diff: replenish
		@docker compose exec -T web ./orm migrations:diff --object-manager doctrine.entitymanager.orm_default
		@docker cp "$(shell docker compose ps -q web)":/code/module/Database/migrations ./module/Database
		@docker compose exec -T web ./orm migrations:diff --object-manager doctrine.entitymanager.orm_report
		@docker cp "$(shell docker compose ps -q web)":/code/module/Report/migrations ./module/Report

migration-up: replenish migration-list
		@docker compose exec web sh -c '. ./scripts/migrate-version.sh && ./orm migrations:execute --up $$migrations --object-manager doctrine.entitymanager.$$alias'

migration-down: replenish migration-list
		@docker compose exec web sh -c '. ./scripts/migrate-version.sh && ./orm migrations:execute --down $$migrations --object-manager doctrine.entitymanager.$$alias'

seed: replenish
		@docker compose exec -T web ./web application:fixtures:load
		@docker compose exec web ./web report:generate:full
		@make preparemailman
		@docker compose exec mailman-web bash -c '(python3 ./manage.py createsuperuser --no-input 2>/dev/null); pkill -HUP uwsgi'
		@docker compose exec -u mailman mailman-core bash -c '(mailman create news@$$MAILMAN_DOMAIN; mailman create other@$$MAILMAN_DOMAIN; true) 2>/dev/null'
		@docker compose exec web ./web database:mailman:fetch

exec:
		docker compose exec -it web $(cmd)

stop:
		@docker compose down --remove-orphans

runtest: loadenv
		@vendor/phpunit/phpunit/phpunit --bootstrap ./bootstrap.php --configuration ./phpunit.xml --stop-on-error --stop-on-failure

runcoverage: loadenv
		@vendor/phpunit/phpunit/phpunit --bootstrap ./bootstrap.php --configuration ./phpunit.xml --coverage-html ./coverage

getvendordir:
		@rm -Rf ./vendor
		@docker cp $(shell docker compose ps -q web):/code/vendor ./vendor

replenish:
		@docker cp ./public "$(shell docker compose ps -q web)":/code
		@docker cp ./module "$(shell docker compose ps -q web)":/code
		@docker compose exec web chown -R www-data:www-data /code/public
		@docker cp ./data "$(shell docker compose ps -q web)":/code
		@docker compose exec web chown -R www-data:www-data /code/data
		@docker compose exec web rm -rf data/cache/module-config-cache.application.config.cache.php
		@docker compose exec web composer dump-autoload --dev
		@docker compose exec web ./orm orm:generate-proxies
		@docker compose exec web /bin/sh -c "EM_ALIAS=orm_report ./orm orm:generate-proxies"

translations:
		@find $(MODULE_DIR) -iname "*.phtml" -print0 | sort -z | xargs -r0 xgettext \
				--language=PHP \
				--from-code=UTF-8 \
				--keyword=translate \
				--keyword=translatePlural:1,2 \
				--output=$(TRANSLATIONS_DIR)/gewisdb.pot \
				--force-po \
				--no-location \
				--package-name=GEWISdb \
				--package-version=$(shell git describe --dirty --always) \
				--copyright-holder=GEWIS && \
		find $(MODULE_DIR) -iname "*.php" -print0 | sort -z | xargs -r0 xgettext \
				--language=PHP \
				--from-code=UTF-8 \
				--keyword=translate \
				--keyword=translatePlural:1,2 \
				--output=$(TRANSLATIONS_DIR)/gewisdb.pot \
				--force-po \
				--no-location \
				--package-name=GEWISdb \
				--package-version=$(shell git describe --dirty --always) \
				--copyright-holder=GEWIS \
				--join-existing && \
		msgattrib --no-obsolete --sort-output -o $(TRANSLATIONS_DIR)/gewisdb.pot $(TRANSLATIONS_DIR)/gewisdb.pot && \
		msgmerge -U $(TRANSLATIONS_DIR)/nl.po $(TRANSLATIONS_DIR)/gewisdb.pot && \
		msgmerge -U $(TRANSLATIONS_DIR)/en.po $(TRANSLATIONS_DIR)/gewisdb.pot && \
		msgattrib --no-obsolete --sort-output -o $(TRANSLATIONS_DIR)/en.po $(TRANSLATIONS_DIR)/en.po && \
		msgattrib --no-obsolete --sort-output -o $(TRANSLATIONS_DIR)/nl.po $(TRANSLATIONS_DIR)/nl.po

update: updatecomposer updatedocker

loadenv: copyprodconf
		@set -o allexport
		@source .env
		@set +o allexport

copyconf:
		cp config/autoload/local.development.php.dist config/autoload/local.php
		cp config/autoload/doctrine.local.development.php.dist config/autoload/doctrine.local.php

copyprodconf:
		@cp config/autoload/local.production.php.dist config/autoload/local.php
		@cp config/autoload/doctrine.local.production.php.dist config/autoload/doctrine.local.php

phpstan:
		@docker compose exec web /bin/sh -c 'echo "" > phpstan/phpstan-baseline-pr.neon'
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G

phpstanpr:
		@git fetch --all
		@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/main
		@git checkout --detach temp-phpstanpr
		@echo "" > phpstan/phpstan-baseline.neon
		@echo "" > phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --generate-baseline phpstan/phpstan-baseline-pr.neon --memory-limit 1G --no-progress
		@git checkout -- phpstan/phpstan-baseline.neon
		@git checkout -
		@docker cp $(shell docker compose ps -q web):/code/phpstan/phpstan-baseline-pr.neon ./phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G --no-progress

psalm: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache

psalmall: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --ignore-baseline

psalmdiff: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --show-info=true

psalmbaseline: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --set-baseline=psalm/psalm-baseline.xml --no-cache

psalmfix: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --alter --issues=InvalidReturnType,InvalidNullableReturnType

phpcs: loadenv
		@vendor/bin/phpcs -p

phpcbf: loadenv
		@vendor/bin/phpcbf -p --filter=GitModified

phpcbfall: loadenv
		@vendor/bin/phpcbf -p

phpcsfix: loadenv
		@vendor/bin/php-cs-fixer fix --format=txt --verbose

phpcsfixrisky: loadenv
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PHP82Migration,@PHP80Migration:risky,-declare_strict_types,-use_arrow_functions  module
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PHP82Migration,@PHP80Migration:risky,-declare_strict_types,-use_arrow_functions  config

checkcomposer: loadenv
		@XDEBUG_MODE=off vendor/bin/composer-require-checker check composer.json
		@vendor/bin/composer-unused

updatecomposer:
		@docker cp ./composer.json $(shell docker compose ps -q web):/code/composer.json
		@docker compose exec web composer update -W
		@docker cp $(shell docker compose ps -q web):/code/composer.lock ./composer.lock

updatedocker:
		@docker compose pull
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/db/gewisdb/web:production -f docker/web/production/Dockerfile .
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/db/gewisdb/web:development -f docker/web/development/Dockerfile .
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/db/gewisdb/nginx:latest -f docker/nginx/Dockerfile docker/nginx

build: buildweb buildnginx

buildprod: buildwebprod buildnginx

builddev: buildwebdev buildnginx

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/db/gewisdb/web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/db/gewisdb/web:development -f docker/web/development/Dockerfile .

buildnginx:
		@docker build -t abc.docker-registry.gewis.nl/db/gewisdb/nginx:latest -f docker/nginx/Dockerfile docker/nginx

buildpgadmin:
		@docker compose build pgadmin

preparemailman:
		@docker compose cp ./docker/mailman/settings_local.py mailman-web:/opt/mailman-web/settings_local.py
		@docker compose exec mailman-web bash -c "pkill -HUP uwsgi"