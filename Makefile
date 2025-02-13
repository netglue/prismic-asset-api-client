# Run `make` (no arguments) to get a short description of what is available
# within this `Makefile`.

help: ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

install: ## Install PHP dependencies
	composer install
.PHONY: install

update: ## Update PHP dependencies
	composer update
.PHONY: update

bump: ## Update PHP dependencies
	composer update
	composer bump -D
	composer update
.PHONY: update

clean: ## Clear out caches
	rm -rf var/cache/phpunit
	rm -f var/cache/phpcs
	vendor/bin/psalm --clear-cache

stan: ## Run PHPStan
	php -d xdebug.mode=off vendor/bin/phpstan analyse
.PHONY: stan

psalm: ## Run Psalm
	php -d xdebug.mode=off vendor/bin/psalm --no-cache
.PHONY: psalm

sa: psalm stan ## Run static analysis checks
.PHONY: sa

cs: ## Run coding standards checks
	php -d xdebug.mode=off vendor/bin/phpcs
.PHONY: cs

test: ## Run unit tests
	vendor/bin/phpunit
.PHONY: test

qa: cs sa test ## Run all QA Checks
.PHONY: check

get-rector: ## Install rector as a dev dependency
ifeq (,$(wildcard ./vendor/bin/rector))
	composer require --dev rector/rector
endif
.PHONY: get-rector

remove-rector: ## Remove rector dependency
	composer remove --dev rector/rector
.PHONY: remove-rector

rector: get-rector ## Run Rector
	vendor/bin/rector
.PHONY: rector

get-require-checker: ## Download a Phar of composer-require-checker
ifeq (,$(wildcard ./vendor/bin/composer-require-checker))
	curl -LsS https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar -o vendor/bin/composer-require-checker
	chmod +x vendor/bin/composer-require-checker
endif
.PHONY: get-require-checker

deps: get-require-checker ## check for un-declared dependencies
	vendor/bin/composer-require-checker -- check
.PHONY: deps
