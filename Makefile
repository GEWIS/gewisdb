.PHONY: help runprod rundev runtest runcoverage update updatecomposer getvendordir phpstan phpcs phpcbf phpcsfix phpcsfixtypes replenish compilelang build buildprod builddev login push pushprod pushdev update all prod dev

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
		@echo "login"
		@echo "push"
		@echo "pushprod"
		@echo "pushdev"
		@echo "update = updatecomposer"
		@echo "all = build login push"
		@echo "prod = buildprod login pushprod"
		@echo "dev = builddev login pushdev"

.DEFAULT_GOAL := all

SHELL = /bin/bash
LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)

runprod:
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

runprodtest: buildprod
		@docker compose -f docker-compose.yml up -d --force-recreate --remove-orphans

rundev: builddev
		@docker compose up -d --remove-orphans
		@make replenish
		@docker compose exec web rm -rf data/cache/module-config-cache.application.config.cache.php

updatedb: rundev
		@docker compose exec -T web ./orm orm:schema-tool:update --force --no-interaction --complete
		@docker compose exec -T web /bin/sh -c "EM_ALIAS=orm_report ./orm orm:schema-tool:update --force --no-interaction --complete"

stop:
		@docker compose down

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
		@docker compose exec web php composer.phar dump-autoload --dev
		@docker compose exec web ./orm orm:generate-proxies
		@docker compose exec web /bin/sh -c "EM_ALIAS=orm_report ./orm orm:generate-proxies"

compilelang:
		msgfmt module/Application/language/en.po -o module/Application/language/en -c --strict -v
		msgfmt module/Application/language/nl.po -o module/Application/language/nl -c --strict -v

getlangfiles:
		./translate-helper

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
		@cp config/autoload/local.test.php.dist config/autoload/local.test.php
		@cp config/autoload/doctrine.local.test.php.dist config/autoload/doctrine.local.test.php

phpstan:
		@docker compose exec web echo "" > phpstan/phpstan-baseline-pr.neon
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G

phpstanpr:
		@git fetch --all
		@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/master
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
		@docker compose exec web php composer.phar selfupdate
		@docker cp $(shell docker compose ps -q web):/code/composer.phar ./composer.phar
		@docker compose exec web php composer.phar update -W
		@docker cp $(shell docker compose ps -q web):/code/composer.lock ./composer.lock

updatedocker:
		@docker compose pull
		@docker build --pull --no-cache -t abc-db.docker-registry.gewis.nl/gewisdb_web:production -f docker/web/production/Dockerfile .
		@docker build --pull --no-cache -t abc-db.docker-registry.gewis.nl/gewisdb_web:development -f docker/web/development/Dockerfile .
		@docker build --pull --no-cache -t abc-db.docker-registry.gewis.nl/gewisdb_nginx:latest -f docker/nginx/Dockerfile docker/nginx

all: build login push

prod: buildprod login pushprod

dev: builddev login pushdev

webprod: buildwebprod login pushwebprod

webdev: buildwebdev login pushwebdev

build: buildweb buildnginx

buildprod: buildwebprod buildnginx

builddev: buildwebdev buildnginx

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc-db.docker-registry.gewis.nl/gewisdb_web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc-db.docker-registry.gewis.nl/gewisdb_web:development -f docker/web/development/Dockerfile .

buildnginx:
		@docker build -t abc-db.docker-registry.gewis.nl/gewisdb_nginx:latest -f docker/nginx/Dockerfile docker/nginx

buildpgadmin:
		@docker compose build pgadmin

login:
		@docker login abc-db.docker-registry.gewis.nl

push: pushweb pushnginx

pushprod: pushwebprod pushnginx

pushdev: pushwebdev pushnginx

pushweb: pushwebprod pushwebdev

pushwebprod:
		@docker push abc-db.docker-registry.gewis.nl/gewisdb_web:production

pushwebdev:
		@docker push abc-db.docker-registry.gewis.nl/gewisdb_web:development

pushnginx:
		@docker push abc-db.docker-registry.gewis.nl/gewisdb_nginx:latest
