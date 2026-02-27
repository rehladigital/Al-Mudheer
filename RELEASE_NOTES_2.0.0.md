# Al Mudheer 2.0.0 - What's New

Release date: 2026-02-25

## Highlights

- Added **Organization Roles** administration page at `Administration -> Organization Roles` for owner/admin governance settings.
- Added **Department Assignments** manager view to list department leads and assigned team members.
- Added **database-backed SMTP settings** management and runtime mailer overrides for secure, per-environment outbound email control.
- Added support for **single-role governance mode** and role-policy toggles in company settings.
- Hardened upgrade reliability with safer **cross-database index migration behavior** in update scripts.

## Functional details

- New organization settings include:
  - single-role per user policy toggle
  - owner/admin edit rights toggle for company settings
  - SMTP host, port, auth, TLS, secure mode, from-address, username, password (password stored encrypted)
- SMTP settings are now loaded at runtime by the mailer from DB keys (`companysettings.smtp.*`) with fallback to environment defaults.
- Manager navigation now includes a dedicated entry for department assignments.
- Company administration navigation now includes an entry for organization role governance.

## Installation and update compatibility

- **Fresh install path** remains compatible and sets baseline settings via schema builder.
- **Update path** includes migration safety improvements:
  - duplicate/existing index creation errors are ignored safely
  - migration remains idempotent across repeated runs
  - avoids MySQL-only index inspection assumptions

## Suggested validation after deploy

- Login and dashboard access work for owner/admin/manager roles.
- `Administration -> Organization Roles` saves and reloads settings correctly.
- `My Work -> Department Assignments` renders expected user mapping for managers.
- SMTP test flow sends successfully using DB-backed credentials.
- Update endpoint confirms DB version is current without migration errors.
