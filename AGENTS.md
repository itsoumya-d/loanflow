# AGENTS.md

## Project

Loanflow is a clean-room regulated lending platform built to demonstrate a senior full-stack brief: Laravel 13 / PHP 8.x, MySQL, JavaScript, OAuth 2.0, Keap CRM, WordPress, BigQuery and Google Tag Manager.

## Commands

```bash
composer install
php artisan key:generate
php artisan passport:keys
touch database/database.sqlite
php artisan migrate --seed
php artisan serve

vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan test
npm run test:gtm
```

## Conventions

- Strict types in application code.
- FormRequests for input validation; Resources for output.
- Every external call has a timeout, bounded retries and an audit record on failure.
- Never commit secrets, `.env`, OAuth keys or SQLite databases.
- Add a test for every behaviour change. Feature tests for HTTP, unit tests for integrations.
- Keep the evidence matrix (`docs/evidence-matrix.md`) current when adding a job-relevant capability.
- Clean-room only: do not copy proprietary code, copy, logos or customer data.

## Architecture

- `app/Services/Lending` — domain services, state machine, audit chain.
- `app/Integrations` — Keap, BigQuery, GTM.
- `app/Http` — API controllers, requests, resources, middleware.
- `wordpress/loanflow-intake` — WordPress plugin.
- `gtm/` — dataLayer contract and custom template.
- `docs/` — architecture, threat model, ADRs, research, evidence matrix.
