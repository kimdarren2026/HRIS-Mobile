# Deployment Checklist

Use this before deploying HRIS Mobile App to any shared demo, staging, or production environment.

## Pre-Deployment

- Confirm branch and commit to deploy.
- Run the test suite:

```bash
cd laravel-app
php artisan test
```

- Back up the database before any migration.
- Back up private uploaded files from `storage/app/private`.
- Confirm no real secrets are committed.
- Confirm `.env` exists on the server and is not tracked by Git.

## Environment

Set production-safe values:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-domain.example
```

Database:

```env
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Session/cache/queue:

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Local/demo SQLite is acceptable only for local portfolio review. Use a real database for shared deployments.

## Install and Build

```bash
cd laravel-app
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

Generate `APP_KEY` only for a new environment:

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on an existing production app with encrypted data or active sessions unless planned.

## Migrations and Seeders

Run migrations safely:

```bash
php artisan migrate --force
```

For a demo environment only, seed demo data:

```bash
php artisan db:seed --force
```

Do not run:

```bash
php artisan migrate:fresh
```

`migrate:fresh` drops all tables and destroys existing data.

## Storage

- Ensure write permissions for:
  - `storage/`
  - `bootstrap/cache/`
- Private attendance selfies and leave attachments are stored under Laravel local/private storage.
- Do not publicly expose `storage/app/private`.
- Run public storage link only if the environment serves public assets from `storage/app/public`:

```bash
php artisan storage:link
```

The current private HRIS upload flows do not require public access to private files.

## HTTPS, Camera, and GPS

- Use HTTPS for shared demo/staging/production.
- Browser camera and geolocation APIs generally require a secure context.
- `http://localhost` is acceptable for local development only.
- Confirm mobile browser permissions for camera and location.

## Cache and Optimization

After environment variables are final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After each deploy:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Final Verification

- `/login` loads.
- Demo role routing works if demo data is seeded.
- Employee attendance page prompts for camera/location.
- Leave attachment access is protected.
- Payroll CSV export works for Finance/Super Admin only.
- Employee payslip access does not expose other employees.
- Admin/Finance/Employee dashboards load.
- Error pages render for forbidden/not found/server error cases.

## Rollback Notes

- Keep a database backup before migration.
- Keep the previous deploy artifact or Git commit available.
- If migration fails, stop traffic, restore DB backup if needed, and redeploy the previous known-good commit.
