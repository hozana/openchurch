build_circleci_config:
	@echo "Compiling circleci config"
	@circleci config pack src > config.yml
	@echo "Validating circleci config"
	@circleci config validate config.yml --org-slug github/hozana
