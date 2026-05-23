# Fixture-app — CI-only

Dit is **geen** productie-site. Het is een minimale site-app die in CI gebruikt wordt om end-to-end smoke tests te draaien op cms-core: rendert de homepage? Werkt het beheer-paneel? Komt GTM in de HTML?

## Lokaal draaien (debugging)

```
cd apps/_fixture-app
composer install
./bin/seed.sh
php -S localhost:8080 -t public
```

Open http://localhost:8080/ — admin login: `fixture-admin` / `fixture-admin-pw`.

## Hoe gebruikt CI deze app?

Zie `.github/workflows/ci.yml`, job `smoke`. CI runt `composer install`, `./bin/seed.sh`, start PHP built-in server, en curlt alle smoke-endpoints.
