# CLAUDE.md

See `AGENTS.md` for project conventions, commands and the architecture map.

Quick verification loop:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan test
npm run test:gtm
```
