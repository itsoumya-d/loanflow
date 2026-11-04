.PHONY: install test lint analyse gtm-test ci serve fresh

install:
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	php artisan passport:keys --force
	touch database/database.sqlite
	php artisan migrate --force
	php artisan db:seed --force

test:
	php artisan test

lint:
	vendor/bin/pint --test

analyse:
	vendor/bin/phpstan analyse --no-progress --memory-limit=1G

gtm-test:
	npm run test:gtm

ci: lint analyse test gtm-test

serve:
	php artisan serve

fresh:
	php artisan migrate:fresh --seed --force
