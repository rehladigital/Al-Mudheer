# Migration Context

This file preserves execution context for the PHP-to-Node migration.

## Current Repository Shape

- Root contains new Node runtime scaffold.
- Legacy PHP codebase is archived in `legacy-php/`.
- External backup created at `D:/cursor/Leantimerehladigital_backup`.

## Implemented in Node (Phase 1 foundation)

- Express + TypeScript runtime:
  - `src/server.ts`
  - `src/config.ts`
- Prisma database layer:
  - SQLite local schema: `prisma/schema.prisma`
  - MySQL Hostinger schema: `prisma/schema.mysql.prisma`
  - Seed file: `prisma/seed.ts`
- DB-driven settings support:
  - plain settings + encrypted settings (`enc::` format)
  - files: `src/settings.ts`, `src/crypto.ts`
- Auth/SSO baseline:
  - SSO-first login with `?advanced=1` local login override
  - Entra OIDC start/callback flow
  - file: `src/auth.ts`

## Verified Commands

- `npm run setup:local`
- `npm run build`
- `npm run dev`
- `GET /health` returns `{"ok":true}`
- `GET /login` returns HTTP 200

## Pending for Full Parity

- Port remaining modules from `legacy-php/`:
  - projects/tasks/boards/tickets
  - dashboards/widgets/reports
  - users/roles/permissions
  - notifications/files/api keys and remaining domain behavior
- Data migration scripts from legacy tables to Prisma models.
- Full regression/UAT before cutover.
