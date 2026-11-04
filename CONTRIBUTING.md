# Contributing

## Local setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan passport:keys
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Create a real OAuth client and copy the one-time secret into the WordPress settings:

```bash
php artisan loanflow:client "WordPress intake"
```

## Quality gates

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan test
npm run test:gtm
```

All four must pass before a pull request is merged. CI also runs the suite on PHP 8.3, 8.4 and 8.5, MySQL 8, and Node 20, 22 and 24.

## Pull request checklist

- [ ] Tests cover the new behaviour (feature or unit).
- [ ] No secrets, credentials or personal data in code, fixtures, logs or commits.
- [ ] Database changes include a reversible migration.
- [ ] New external calls have timeouts, retries and failure handling.
- [ ] New endpoints have authentication, authorisation and validation.
- [ ] Documentation is updated, including the evidence matrix when a job requirement is added.
