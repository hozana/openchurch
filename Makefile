INFRA_PATH := ./../ # Path to infra folder, relative to ./infra/backend
MAKEFLAGS += --no-print-directory

.DEFAULT_GOAL := help

CIRCLECI := $(shell command -v circleci 2> /dev/null)
DOCKER_COMPOSE_EXEC_FLAGS := $(shell if [ -t 0 ] && [ -t 1 ]; then echo ""; else echo "-T"; fi)

.PHONY: help
help: ## Display available commands
	@echo "Available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "For detailed usage of each command, check docs/makefile-helpers.md"


#################################
# RUN IN ISOLATION jakzal/phpqa #
#################################

.PHONY: qa-shell
qa-shell: setup-git-hooks ## Runs a command in the jakzal/phpqa:php8.5 container
	@(docker pull jakzal/phpqa:php8.5 > /dev/null; \
        docker run --init --rm \
		--volume "$(CURDIR):/data/code" \
		-v /tmp/tmp-phpqa:/tmp \
		--workdir /data/code \
		jakzal/phpqa:php8.5 \
		sh -c "$(cmd)")

###############################
# DOCKER                      #
###############################

.PHONY: shell
shell: ## Runs a command in the php-api container
	(cd $(INFRA_PATH) && docker compose exec $(DOCKER_COMPOSE_EXEC_FLAGS) php-api bash $(if $(cmd),-lc "$(cmd)"))

###############################
# DEPENDENCIES                #
###############################

.PHONY: composer-install
composer-install: ## Install composer dependencies
	$(MAKE) shell cmd="composer install"

.PHONY: composer-update
composer-update: ## Update composer dependencies
	$(MAKE) shell cmd="composer update && composer bump"

###############################
# TESTS                       #
###############################

.PHONY: test
test: ## Run PHPUnit tests with optional filter
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --filter='$(filter)' --verbose --debug"

.PHONY: test-skip-checks
test-skip-checks: ## Run PHPUnit tests skipping bootstrap checks: test-skip-checks filter=RemoveSubscri
	$(MAKE) shell cmd="SKIP_BOOTSTRAP_CHECKS=true php -d memory_limit=15000M ./vendor/bin/phpunit --filter='$(filter)' --verbose --debug"

.PHONY: test-unit
test-unit: ## Run Unit Tests suite (mirrors CI phpunit_unit)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Unit Tests'"

.PHONY: test-elastic
test-elastic: ## Run Elastic search Tests suite (mirrors CI phpunit_elastic)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'ElasticSearch Tests'"

.PHONY: test-api
test-api: ## Run Integration Api Tests, Mail excluded (mirrors CI phpunit_integration_api)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Api/ Tests'"

.PHONY: test-api-mail
test-api-mail: ## Run Integration Api Mail Tests (mirrors CI phpunit_integration_api_mail)
	$(MAKE) shell cmd="DUMP_MAILS=1 php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Api Mail Tests'"

.PHONY: test-crm
test-crm: ## Run Integration Crm Tests, DonationSolicitations excluded (mirrors CI phpunit_integration_crm)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Crm Tests'"

.PHONY: test-crm-donation-solicitations
test-crm-donation-solicitations: ## Run Integration Crm Donation Solicitations Tests (mirrors CI phpunit_integration_crm_donation_solicitations)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Crm Donation Solicitations Tests'"

.PHONY: test-dbal
test-dbal: ## Run Integration Dbal Tests (mirrors CI phpunit_integration_dbal)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Dbal Tests'"

.PHONY: test-admin-core-file
test-admin-core-file: ## Run Integration Admin/Core/File Tests (mirrors CI phpunit_integration_admin_core_file)
	$(MAKE) shell cmd="php -d memory_limit=15000M ./vendor/bin/phpunit --verbose --debug --testsuite 'Integration Admin Core File Tests'"

###############################
# CODE QUALITY                #
###############################

.PHONY: cs-fix
cs-fix: ## Fix code style issues
	$(MAKE) qa-shell cmd="/tools/php-cs-fixer --using-cache=no -vvv --no-ansi --config=.php-cs-fixer.php --diff fix"

.PHONY: cs-fix-dry-run
cs-fix-dry-run: ## Preview code style fixes without applying them, errors must not be ignore to have hooks working
	$(MAKE) qa-shell cmd="/tools/php-cs-fixer --using-cache=no -vvv --no-ansi --config=.php-cs-fixer.php --dry-run --diff fix"

.PHONY: phpcbf
phpcbf: ## Fix code style with PHP Code Beautifier
	$(MAKE) qa-shell cmd=phpcbf

.PHONY: phpcs
phpcs: ## Check code style with PHP CodeSniffer
	$(MAKE) qa-shell cmd=phpcs

.PHONY: phpmd
phpmd: ## Run PHP Mess Detector
	$(MAKE) qa-shell cmd="phpmd -vvv --suffixes='php,phtml' ./src text phpmd.xml"

.PHONY: phpmd-generate-baseline
phpmd-generate-baseline: ## Generate PHP Mess Detector baseline
	$(MAKE) qa-shell cmd="phpmd --generate-baseline ./src text phpmd.xml"

.PHONY: stan
stan: ## Run PHPStan static analysis
	$(MAKE) shell cmd="php -d memory_limit=2G ./vendor/bin/phpstan analyse -c phpstan.neon $(path)"

.PHONY: stan-baseline
stan-baseline: ## Generate PHPStan baseline
	$(MAKE) shell cmd="php -d memory_limit=2G ./vendor/bin/phpstan analyse -c phpstan.neon --generate-baseline"

.PHONY: schema-validate
schema-validate: ## Validate database schema
	$(MAKE) shell cmd="bin/console doctrine:schema:validate -vvv && bin/console doctrine:schema:validate -vvv --em=crm"

.PHONY: rector
rector: ## Run Rector refactoring
	$(MAKE) qa-shell cmd="php -d memory_limit=2G ./vendor/bin/rector process --clear-cache"

.PHONY: rector-dry-run
rector-dry-run: ## Preview Rector changes without applying them
	$(MAKE) qa-shell cmd="php -d memory_limit=2G ./vendor/bin/rector process --clear-cache --dry-run"

.PHONY: composer-audit
composer-audit: ## Run Composer audit
	$(MAKE) qa-shell cmd="echo '\nComposer audit:'; composer audit; echo ''"

.PHONY: symfony-security-check
symfony-security-check: ## Run Symfony security check
	$(MAKE) qa-shell cmd="if [ ! -f /tmp/symfony ]; \
        then curl -Ls https://github.com/symfony-cli/symfony-cli/releases/download/v5.17.1/symfony-cli_linux_amd64.tar.gz | \
        tar -zxv -C /tmp symfony; fi; \
		/tmp/symfony check:security; \
        echo ''"

.PHONY: circleci-validate
circleci-validate: ## Validate CircleCI configuration
ifndef CIRCLECI
	@echo "Installing CircleCI CLI..."
	@curl -fLSs https://raw.githubusercontent.com/CircleCI-Public/circleci-cli/main/install.sh | bash
	$(eval CIRCLECI := circleci)
endif
	$(CIRCLECI) config validate .circleci/config.yml --org-slug github/hozana

.PHONY: qa
qa: ## Run all quality checks
	-$(MAKE) baselines-check
	$(MAKE) symfony-security-check
	$(MAKE) composer-audit
	$(MAKE) rector
	$(MAKE) cs-fix
	$(MAKE) stan
	$(MAKE) phpmd
	$(MAKE) schema-validate

.PHONY: baselines-check
baselines-check: ## Show detailed baseline errors for files modified in current branch
	./bin/scripts/baselines-check.sh

.PHONY: baselines
baselines: phpmd-generate-baseline stan-baseline # Re-generate baselines

###############################
# ASSETS                      #
###############################

.PHONY: npm-watch
npm-watch: ## Runs npm run watch
	$(MAKE) shell cmd="npm run watch"

.PHONY: npm-build
npm-build: ## Runs npm run build
	$(MAKE) shell cmd="npm run build"

.PHONY: build-mails
build-mails: ## Build mail assets
	$(MAKE) shell cmd="npm run build-prod-mails"

.PHONY: build-admin
build-admin: ## Build admin assets
	$(MAKE) shell cmd="npm run build-prod-admin"

.PHONY: build-crm
build-crm: ## Build CRM assets
	$(MAKE) shell cmd="npm run build-prod-crm"

.PHONY: build-client
build-client: ## Build client assets
	$(MAKE) shell cmd="npm run build-prod-client"

###############################
# FIXTURES                    #
###############################

.PHONY: fixture_crm
fixture_crm: ## Load CRM fixtures
	$(MAKE) shell cmd="bin/console doctrine:fixtures:load --em=crm --group=crm_minimal -vv -n --purger=custom_purger"

.PHONY: fixture_hoz
fixture_hoz: ## Load HOZ fixtures
	$(MAKE) shell cmd="bin/console doctrine:fixtures:load --group=hoz_minimal -vv -n --purger=custom_purger"

###############################
# ASSETS                      #
###############################

.PHONY: build-assets
build-assets: ## Install yarn dependencies, build assets, and install Symfony assets
	$(MAKE) shell cmd="yarn install"
	$(MAKE) shell cmd="yarn build"
	$(MAKE) shell cmd="./bin/console asset:install"

###############################
# Setup Git Hooks             #
###############################
.PHONY: setup-git-hooks
setup-git-hooks: ## Configure git hooks for the main project and its submodules
	@echo "Configuring git hooks path..."
	git config core.hooksPath git-hooks