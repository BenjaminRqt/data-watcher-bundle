.PHONY: qa fix phpunit phpstan phpcs-check

PHPUNIT = ./vendor/bin/simple-phpunit
PHPCS   = ./vendor/bin/php-cs-fixer
PHPSTAN = ./vendor/bin/phpstan

qa: phpcs-check phpstan phpunit

fix:
	$(PHPCS) fix --allow-risky=yes

phpunit:
	$(PHPUNIT)

phpstan:
	$(PHPSTAN) analyse

phpcs-check:
	$(PHPCS) fix --dry-run --diff --allow-risky=yes
