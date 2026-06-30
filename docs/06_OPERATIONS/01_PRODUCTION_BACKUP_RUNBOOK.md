# Phase 33 — Production Backup and DevOps Runbook

## Production Context

| Item | Value |
| --- | --- |
| Production URL | `https://hrismobile.my.id` |
| Production VPS | `157.15.125.237` |
| Production repo path | `/var/www/hrismobile` |
| Laravel production path | `/var/www/hrismobile/laravel-app` |
| Deploy/server user | `hrisdeploy` |

## Current Audit Findings

- Production app was at Phase 30 commit `fbd44af` during the audit.
- Phase 31 and Phase 32A were documentation-only and do not require deploy.
- HRIS database size was about `1.23 MB`.
- Laravel storage size was about `1.5 MB`.
- HRIS app folder size was about `127 MB`.
- Disk usage was initially about `84%`, then reduced to `79%` after clearing a non-HRIS PM2 log.
- The large disk usage was not caused by HRIS Mobile.
- Non-HRIS large items found included `/root/.pm2/logs/bot-wa-out.log` and `/home/env`.

## Backup Created

| Item | Value |
| --- | --- |
| Backup directory | `/var/backups/hrismobile` |
| Backup timestamp | `20260630_081527` |
| Database backup | `20260630_081527_database.sql.gz` |
| Storage backup | `20260630_081527_storage_app_public.tar.gz` |
| Checksum file | `20260630_081527_SHA256SUMS.txt` |
| Checksum verification result | OK |

## Backup Policy Recommendation

- Always back up database before deploy or destructive maintenance.
- Always back up `storage/app/public` because it may contain uploads/selfies/attachments.
- Store production backups outside the app directory.
- Restrict backup directory permissions.
- Keep local/on-server backup only as short-term safety.
- For real campus production, add external/offsite backup storage.
- Do not store `.env` content or database passwords in documentation.

## Production Manual Backup Command

Run this only on the production server as an authorized operator. The example reads database settings from `.env` into shell variables without printing secrets to the terminal or writing secrets to documentation.

```bash
cd /var/www/hrismobile/laravel-app

BACKUP_TS="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="/var/backups/hrismobile"

sudo install -d -m 750 -o hrisdeploy -g hrisdeploy "$BACKUP_DIR"

read_env() {
    local key="$1"
    grep -E "^${key}=" .env | tail -n 1 | cut -d '=' -f 2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_HOST_VALUE="$(read_env DB_HOST)"
DB_PORT_VALUE="$(read_env DB_PORT)"
DB_DATABASE_VALUE="$(read_env DB_DATABASE)"
DB_USERNAME_VALUE="$(read_env DB_USERNAME)"
DB_PASSWORD_VALUE="$(read_env DB_PASSWORD)"

MYSQL_PWD="$DB_PASSWORD_VALUE" mysqldump \
    --single-transaction \
    --quick \
    --lock-tables=false \
    -h "${DB_HOST_VALUE:-127.0.0.1}" \
    -P "${DB_PORT_VALUE:-3306}" \
    -u "$DB_USERNAME_VALUE" \
    "$DB_DATABASE_VALUE" \
    | gzip -9 > "$BACKUP_DIR/${BACKUP_TS}_database.sql.gz"

tar -czf "$BACKUP_DIR/${BACKUP_TS}_storage_app_public.tar.gz" \
    -C /var/www/hrismobile/laravel-app \
    storage/app/public

(
    cd "$BACKUP_DIR"
    sha256sum \
        "${BACKUP_TS}_database.sql.gz" \
        "${BACKUP_TS}_storage_app_public.tar.gz" \
        > "${BACKUP_TS}_SHA256SUMS.txt"
)

chmod 640 "$BACKUP_DIR/${BACKUP_TS}_database.sql.gz" \
    "$BACKUP_DIR/${BACKUP_TS}_storage_app_public.tar.gz" \
    "$BACKUP_DIR/${BACKUP_TS}_SHA256SUMS.txt"

unset DB_PASSWORD_VALUE MYSQL_PWD
```

## Verification

Verify checksums after backup creation:

```bash
cd /var/backups/hrismobile
sha256sum -c 20260630_081527_SHA256SUMS.txt
```

List the backup files:

```bash
ls -lh /var/backups/hrismobile/20260630_081527_*
```

## Restore Caution

- Restore should not be run casually.
- Restore must be tested on staging first.
- Never run restore on production without explicit approval.
- Never use `migrate:fresh`.
- Never use `git reset --hard`.
- Never use `git clean`.

## Acceptance Criteria

- Backup runbook exists.
- Production backup was created and checksum verified.
- No application code changed.
- No `.env` changed.
- No deploy or migration run.
