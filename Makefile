# Run `make` (no arguments) to get a short description of what is available
# within this `Makefile`.

help: ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

qa: cs sa test deps ## Run all QA checks

test: ## Run tests
	time -p php -dxdebug.mode=off ./vendor/bin/phpunit
.PHONY: test

get-require-checker: ## Download a Phar of composer-require-checker
ifeq (,$(wildcard ./vendor/bin/composer-require-checker))
	curl -LsS https://github.com/maglnet/ComposerRequireChecker/releases/download/4.14.0/composer-require-checker.phar -o vendor/bin/composer-require-checker
	chmod +x vendor/bin/composer-require-checker
endif
.PHONY: get-require-checker

deps: get-require-checker ## Check for un-declared dependencies
	php -dxdebug.mode=off -f vendor/bin/composer-require-checker -- check
.PHONY: deps

bump: ## Bump Composer deps
	composer update
	composer bump --dev-only
	composer update
.PHONY: bump

sa: ## Run static analysis
	php -dxdebug.mode=off vendor/bin/psalm --no-cache
.PHONY: sa

update-baseline: ## Update SA Baseline removing fixed issues
	php -dxdebug.mode=off vendor/bin/psalm --no-cache --update-baseline
.PHONY: update-baseline

set-baseline: ## Baseline outstanding SA Issues
	php -dxdebug.mode=off vendor/bin/psalm --no-cache --set-baseline=psalm-baseline.xml
.PHONY: set-baseline

cs: ## Verify coding standards
	php -dxdebug.mode=off vendor/bin/phpcs
.PHONY: cs

csfix: ## Auto-fix coding standard rules, where possible
	php -dxdebug.mode=off vendor/bin/phpcbf
.PHONY: csfix

clean: ## Delete caches and logs
	rm -rf cache/phpunit; \
	rm -f cache/infection.*; \
	rm -f cache/phpcs;
.PHONY: clean

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
