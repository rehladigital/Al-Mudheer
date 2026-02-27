# Al Mudheer

Al Mudheer is a project and work management platform customized for Rehla Digital.

## Project Sponsor

<div align="center">
  <strong>Rehla Digital Inc</strong><br/><br/>
  <img src="public/assets/images/logo-login.png" alt="Rehla Digital Inc Logo" width="280" />
</div>

Acknowledgement: This project is built based on the Leantime open-source project.

## Runtime status

- **Production runtime:** PHP app from repository root (this is the complete app with full theme and modules).
- **Node migration track:** preserved under `node-migration/` for future modernization work; it is not the active production path.
- **Release target:** `2.0.0` tag on `main`.

## About Rehla Digital

Rehla Digital (`rehladigital.com`) positions itself as a data analytics and digital growth company focused on helping businesses turn data into decisions and visibility into revenue. Their public service areas include data and analytics consulting, SEO, PPC, social media management, web development, and Google Business Profile optimization, with a results-oriented approach centered on measurable business outcomes.

Website: [https://rehladigital.com](https://rehladigital.com)

## Why this fork exists

- Company-specific branding and UI customization
- SSO-first authentication flow
- Offline-friendly behavior with no outbound marketplace/help/update checks
- Database-driven configuration for operational settings

## Core capabilities

- Task planning with kanban, list, table, calendar, and timeline views
- Personal "My Kanban" shortcut on dashboard for cross-project assigned tasks
- Project dashboards, milestones, goals, and reporting
- Team collaboration with comments, files, and documentation
- Role-based access control and project-level assignments
- Timesheets and work tracking (planned for a future release)

## System requirements

- PHP 8.2+
- MySQL/MariaDB or SQLite
- Apache/Nginx/IIS (with required routing support)
- Standard PHP extensions required by this project

## Local setup (quick start)

1. Clone this repository
2. Configure `config/.env` with required values
3. Point web root to `public/`
4. Open `/install` and complete setup

## Hostinger shared hosting (validated)

Great news: this app is confirmed to run on Hostinger shared hosting (no root access) with full UI assets and OIDC flow.

### Exact deployment steps

1. Create a subdomain/domain in Hostinger (example: `pm.example.com`).
2. Upload the repository to:
   - `/home/<user>/domains/<domain>/public_html/<app-folder>`
3. In Hostinger PHP settings, use PHP `8.2+` (recommended `8.3`).
4. Create `config/.env` (or `<app-folder>/.env`) with your DB + app settings:
   - `LEAN_APP_URL='https://<your-domain>'`
   - `LEAN_DB_DEFAULT_CONNECTION='mysql'`
   - `LEAN_DB_HOST`, `LEAN_DB_DATABASE`, `LEAN_DB_USER`, `LEAN_DB_PASSWORD`, `LEAN_DB_PORT`
   - `LEAN_SESSION_PASSWORD='<random-strong-value>'`
   - OIDC values if SSO is required (`LEAN_OIDC_ENABLE`, `LEAN_OIDC_CLIENT_ID`, `LEAN_OIDC_CLIENT_SECRET`, `LEAN_OIDC_PROVIDER_URL`)
5. Ensure root rewrite forwards requests to `public/`:
   - file: `<app-folder>/.htaccess`
6. Ensure front controller rewrite is present:
   - file: `<app-folder>/public/.htaccess`
7. Install production dependencies:
   - `php composer.phar install --no-dev --prefer-dist -o --ignore-platform-reqs`
8. Clear runtime caches:
   - `/opt/alt/php83/usr/bin/php bin/leantime cache:clearAll`
9. Confirm static assets are present and directly accessible:
   - `<app-folder>/public/dist/**`
   - `<app-folder>/public/theme/**`
   - URLs like `https://<your-domain>/dist/css/main.2.0.0.min.css` must return `200`
10. Open `https://<your-domain>/auth/login` and complete login/install verification.

### Post-deploy verification checklist

- `GET /auth/login` returns `200`
- `GET /dist/css/main.2.0.0.min.css` returns `200`
- `GET /dist/js/compiled-frameworks.2.0.0.min.js` returns `200`
- OIDC start endpoint redirects to your provider
- Successful login reaches `/dashboard/home`

### One-command verification script

- Run all checks:
  - `SITE_URL=https://<your-domain> bash scripts/deploy-checklist.sh`
- Include authenticated dashboard validation:
  - `LOGIN_USERNAME=<email> LOGIN_PASSWORD=<password> SITE_URL=https://<your-domain> bash scripts/deploy-checklist.sh`

### In-app version updater (owner only)

- Location: `Administration -> Company Settings -> Version Updater`
- Purpose: update app files with either:
  - Git tag update (if git exists on server), or
  - Manual ZIP package upload (works on Hostinger shared hosting without git).
- ZIP update flow:
  - download release ZIP from repository
  - upload ZIP in `Version Updater`
  - system updates files while preserving runtime paths
  - runs `composer install --no-dev --prefer-dist -o --ignore-platform-reqs`
  - runs `php bin/leantime cache:clearAll`
- Safety guarantees:
  - does **not** modify `config/.env`
  - does **not** reset DB/install state
  - does **not** delete `storage/` or `userfiles/`
  - does **not** overwrite user settings records in DB
- Update notification:
  - `Version Updater` shows latest release and flags when a newer version is available.

### Recommended FTP update command (safe sync)

Use this script for future deployments to avoid overwriting runtime data:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/ftp-safe-sync.ps1 `
  -Host "185.162.54.190" `
  -Port 65002 `
  -User "<hostinger-user>" `
  -Password "<hostinger-password>" `
  -RemotePath "/home/<user>/domains/<domain>/public_html/pm" `
  -SourcePath "."
```

This sync excludes `config/.env`, `storage/`, `userfiles/`, local logs, databases, and developer-only folders.

## Setup helper files

- `composer.phar`: local Composer binary included for environments without a global Composer install.
- `manual_sqlite_install.php`: helper script to bootstrap SQLite install data.

Use environment variables with `manual_sqlite_install.php`:

`ALMUDHEER_ADMIN_EMAIL`, `ALMUDHEER_ADMIN_PASSWORD`, `ALMUDHEER_ADMIN_FIRSTNAME`, `ALMUDHEER_ADMIN_LASTNAME`, `ALMUDHEER_COMPANY_NAME`, `ALMUDHEER_COMPANY_COUNTRY`, `ALMUDHEER_CURRENCY_CODE`, `ALMUDHEER_TIMEZONE`

Default helper-script login (if env vars are not set):
- Email: `admin@admin.com`
- Password: `admin123`
- Change this immediately in any non-local/shared environment.

## Development

- Run frontend/build tools as configured in this repository
- Use the local PHP server for quick testing:

```bash
php -S localhost:8090 -t public
```

## Notes

- App title and branding are set to **Al Mudheer**
- Login flow is configured to prioritize SSO
- Most runtime settings are managed in the database
- Timesheets and work tracking are planned for a future release

## License

This project is distributed under AGPLv3, including plugin-related license exceptions defined by the codebase.
