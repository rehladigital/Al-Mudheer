# Al Mudheer Node Migration

This repository root now contains the Node migration runtime.

- Legacy PHP code is archived in `legacy-php/`
- External backup copy is at `D:\cursor\Leantimerehladigital_backup`

## Runtime targets

- Local development: SQLite
- Hostinger deployment: MySQL/MariaDB with Node app runtime

## Quick start (local)

1. Copy env file:
   - `Copy-Item .env.example .env`
2. Update required values in `.env`:
   - `APP_URL`
   - `SESSION_SECRET`
   - `ENCRYPTION_KEY`
3. Install dependencies:
   - `npm install`
4. Initialize database:
   - `npm run setup:local`
5. Start app:
   - `npm run dev`

Open: `http://localhost:3000/login`

## Hostinger (Node app)

Use Hostinger Node.js app deployment flow:
- [How to add a Node.js Web App in Hostinger](https://www.hostinger.com/support/how-to-deploy-a-nodejs-website-in-hostinger/)

Set environment variables in Hostinger panel:

- `DATABASE_PROVIDER=mysql`
- `DATABASE_URL` (your Hostinger MySQL connection URL)
- `APP_URL` (your domain URL)
- `SESSION_SECRET`
- `ENCRYPTION_KEY`
- `SSO_ENABLED`
- `OIDC_ISSUER`
- `OIDC_CLIENT_ID`
- `OIDC_CLIENT_SECRET`

Then run:

- `npm install`
- `npm run build`
- `npm run setup:hostinger`
- `npm run start`

## What is already ported

- DB-backed company settings (`zp_settings`)
- Encrypted setting persistence (`enc::` prefix format)
- SSO-first login behavior with `?advanced=1` override
- Microsoft Entra OIDC start/callback flow

## Next migration work

- Port complete Leantime module set from `legacy-php/`
- Add full role/permission parity and full UI parity
- Add full data migration scripts from legacy schema
