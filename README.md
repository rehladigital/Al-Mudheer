# Al Mudheer

Al Mudheer is a project and work management platform customized for Rehla Digital.

## Project Sponsor

<div align="center">
  <strong>Rehla Digital Inc</strong><br/><br/>
  <img src="public/assets/images/logo-login.png" alt="Rehla Digital Inc Logo" width="280" />
</div>

Acknowledgement: This project is built based on the Leantime open-source project.

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
- Project dashboards, milestones, goals, and reporting
- Team collaboration with comments, files, and documentation
- Role-based access control and project-level assignments
- Timesheets and work tracking

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

## Setup helper files

- `composer.phar`: local Composer binary included for environments without a global Composer install.
- `manual_sqlite_install.php`: helper script to bootstrap SQLite install data.

Use environment variables with `manual_sqlite_install.php`:

`ALMUDHEER_ADMIN_EMAIL`, `ALMUDHEER_ADMIN_PASSWORD`, `ALMUDHEER_ADMIN_FIRSTNAME`, `ALMUDHEER_ADMIN_LASTNAME`, `ALMUDHEER_COMPANY_NAME`, `ALMUDHEER_COMPANY_COUNTRY`, `ALMUDHEER_CURRENCY_CODE`, `ALMUDHEER_TIMEZONE`

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

## License

This project is distributed under AGPLv3, including plugin-related license exceptions defined by the codebase.
