---
name: run-sysdoc-back
description: Run, start, test, or smoke-test the sysdoc_back Laravel API. Use when asked to launch the backend, verify API endpoints, check authentication, or confirm API changes work.
---

# run-sysdoc-back

Laravel 10 REST API for the Sysdoc system (Jr Ferragens). Driven by `curl`-based smoke tests in `.claude/skills/run-sysdoc-back/smoke.sh`.

All commands below were verified on this machine (PHP 8.2, MySQL 8.0, Windows 11).

---

## Prerequisites

- PHP 8.2 in PATH (`php --version`)
- MySQL 8.0 running as Windows service (`MySQL80`)
- Composer installed globally

## Setup

```bash
cd sysdoc_back
composer install
cp .env.example .env          # if .env is missing
php artisan key:generate
```

DB credentials in `.env` (already configured):
```
DB_DATABASE=sysdoc
DB_USERNAME=root
DB_PASSWORD=admin
```

## Run (agent path)

Start the server in the background, then run the smoke script:

```bash
cd sysdoc_back
php artisan serve --port=8000 &
sleep 3
bash .claude/skills/run-sysdoc-back/smoke.sh
```

Smoke script tests: server reachability → login → authenticated endpoints (`/api/user`, `/api/clients`, `/api/users`) → public endpoints (`/api/public/pharmacy/medicines/daily`, `/api/public/pharmacy/medicines/monthly-acquisitions`).

Override credentials if the DB has a different admin password:
```bash
SMOKE_CPF=08449222699 SMOKE_PASS=12345678 bash .claude/skills/run-sysdoc-back/smoke.sh
```

### One-off API calls

```bash
# Login and get a token
TOKEN=$(curl -s http://localhost:8000/api/login \
  -X POST -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"cpf":"08449222699","password":"12345678"}' | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# Any authenticated endpoint
curl -s http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

### Direct invocation (no HTTP)

For testing business logic without starting the server:

```bash
cd sysdoc_back
php artisan tinker --execute="echo App\Models\User::count();"
php artisan tinker --execute="echo json_encode(DB::table('users')->first());"
```

## Run (human path)

```bash
cd sysdoc_back
php artisan serve
# API at http://localhost:8000
# Ctrl-C to stop
```

## Tests

```bash
cd sysdoc_back
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Feature/SomeTest.php  # single file
```

## Gotchas

- **Login field is `cpf`, not `email`.** The `Auth::attempt()` call uses the `cpf` column. Sending `email` returns a 422 validation error, not 401.
- **`mysql` CLI not in bash PATH.** MySQL runs as a Windows service but the client binary isn't on the Bash PATH. Use `php artisan tinker` for DB queries instead.
- **`/api/login` returns HTML on browser.** Must send `Accept: application/json` or Laravel returns a redirect HTML page instead of JSON.
- **Default password may differ from seeder.** The seeder sets `12345678` but production users have real passwords. The smoke script reads `SMOKE_CPF`/`SMOKE_PASS` env vars.
- **`php artisan serve` runs in foreground.** Background it with `&` or use a separate terminal when running smoke tests.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `php artisan serve` hangs on "Starting Laravel development server" | Port 8000 already in use. Use `--port=8001` or kill the existing process. |
| `{"message":"Usuário ou senha inválidos."}` | Wrong password in DB. Reset: `php artisan tinker --execute="\$u=App\Models\User::first(); \$u->password=bcrypt('12345678'); \$u->save();"` |
| `SQLSTATE[HY000]` connection refused | MySQL service not running. Start it: `net start MySQL80` (run as admin in PowerShell). |
| 500 on any route | Check storage permissions: `php artisan cache:clear && php artisan config:clear` |
