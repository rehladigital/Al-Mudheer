# Al Mudheer

Al Mudheer is a project and work management platform customized for Rehla Digital.

## Version

- **Current release:** `3.50`

## Access Control Documentation

- RBAC logic is documented in `RBAC.md`.
- Open: [RBAC.md](RBAC.md)

## Fresh Installation Guide (Download and Install)

Use this guide for a clean new installation on a new server or Hostinger hosting account.

## 1) Server Requirements

- PHP `8.2+` (recommended `8.3`)
- MySQL/MariaDB (recommended for production) or SQLite
- Apache/Nginx with URL rewrite enabled
- Required PHP extensions normally used by this project

## 2) Download and Upload Files

1. Download the latest release ZIP from GitHub Releases (recommended for direct install).
   - Example release page: `https://github.com/rehladigital/Al-Mudheer/releases`
   - Download the `Source code (zip)` for version `3.50` (or latest).
2. Extract and upload all files to your app folder, for example:
   - `/home/<user>/domains/<domain>/public_html/almudheer`
3. Keep writable runtime folders available (if used in your environment):
   - `storage/`
   - `userfiles/`

## 3) Configure Environment

Create or update `config/.env` with your real values.

```env
LEAN_APP_URL='https://pm.yourdomain.com'
LEAN_DB_DEFAULT_CONNECTION='mysql'
LEAN_DB_HOST='localhost'
LEAN_DB_DATABASE='your_db_name'
LEAN_DB_USER='your_db_user'
LEAN_DB_PASSWORD='your_db_password'
LEAN_DB_PORT='3306'
LEAN_SESSION_PASSWORD='generate-a-long-random-secret'
```

If you use Microsoft/SSO login, also set your OIDC keys in the same file.

## 4) Web Root and Rewrite Rules

- Point your domain/subdomain document root to the app `public/` directory when possible.
- If your host does not support that directly, keep the project in one folder and ensure `.htaccess` rewrite routes requests to `public/index.php`.
- Confirm `public/.htaccess` is present.

## 5) Install Dependencies

From project root:

```bash
php composer.phar install --no-dev --prefer-dist -o --ignore-platform-reqs
```

Hostinger-compatible PHP path example:

```bash
/opt/alt/php83/usr/bin/php composer.phar install --no-dev --prefer-dist -o --ignore-platform-reqs
```

## 6) Run Initial Installer

1. Open `https://pm.yourdomain.com/install`
2. Complete the setup wizard.
3. Create admin user credentials.

## 7) Run Post-Install Update and Cache Clear

From project root:

```bash
/opt/alt/php83/usr/bin/php bin/leantime system:update
/opt/alt/php83/usr/bin/php bin/leantime cache:clearAll
```

If you are not on Hostinger, use your server's PHP binary path.

## 8) Validation Checklist

- `https://pm.yourdomain.com/auth/login` returns HTTP `200`
- Login works with your admin account
- Dashboard opens successfully
- Static assets load (CSS/JS without 404/500)

## 9) Optional: SQLite Quick Bootstrap

`manual_sqlite_install.php` can be used for helper bootstrap scenarios.
Set values through environment variables:

- `ALMUDHEER_ADMIN_EMAIL`
- `ALMUDHEER_ADMIN_PASSWORD`
- `ALMUDHEER_ADMIN_FIRSTNAME`
- `ALMUDHEER_ADMIN_LASTNAME`
- `ALMUDHEER_COMPANY_NAME`
- `ALMUDHEER_COMPANY_COUNTRY`
- `ALMUDHEER_CURRENCY_CODE`
- `ALMUDHEER_TIMEZONE`

## 10) Production Deployment Notes

- Recommended branch flow: `develop` -> `prod` -> `main`
- Production deploy is handled by GitHub Actions workflow:
  - `.github/workflows/deploy-prod.yml`
- Never commit secrets; keep credentials only in:
  - server `config/.env`
  - GitHub repository secrets

## License

This project is distributed under AGPLv3, including plugin-related license exceptions defined by the codebase.
