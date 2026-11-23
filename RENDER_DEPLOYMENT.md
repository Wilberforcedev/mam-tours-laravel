# Deploying MAM Tours to Render

This repository deploys as a Docker web service. Render's managed database is PostgreSQL, so the service uses Laravel's `pgsql` connection. PHP is not a native Render runtime.

## Before creating the Blueprint

1. Select a paid web service plan. The Blueprint attaches a persistent disk, which requires a paid service plan and prevents horizontal scaling of that service.
2. Choose a production Render Postgres plan with backups and retention suitable for customer and booking data. The Blueprint intentionally does not select a paid database plan.
3. Prepare SMTP credentials from a transactional email provider. Mailtrap's sandbox credentials are not suitable for customer email.
4. Review the current Laravel version and dependency support policy before launch. This application still uses Laravel 8 and requires a supported-framework upgrade before a production launch.

## Create the Render resources

Create a Render Blueprint from this repository and sync `render.yaml`. It defines a Docker web service and a managed Postgres database. The Docker image includes PDO drivers for PostgreSQL and MySQL; Render uses PostgreSQL.

The web service mounts a persistent disk at `storage/app`. This preserves public uploads and private KYC files across deploys and restarts. The disk is available to the web process at startup, so migrations run after the disk mounts and before Apache accepts traffic. Keep this service to one instance.

On the first Blueprint sync, provide these secret environment values:

- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_ADMIN_EMAIL`

For an existing Render service, add the new values manually in its Environment settings; Render does not prompt for `sync: false` values on later Blueprint syncs.

## Runtime behavior

Apache serves the Laravel application. Supervisor keeps Apache, Laravel's scheduler, and a database queue worker running in the same container. The scheduler handles booking reminders, booking-hold expiry, and fleet reminders. The queue uses PostgreSQL; queued mail and other jobs are processed by the worker. This one-container setup is intended for one web instance and should be replaced with separate worker/scheduler services before horizontal scaling.

Customer uploads persist on Render's disk, and KYC files remain outside the public storage symlink. Back up legacy uploaded files before the first deployment and restore them to the persistent disk; Render's default filesystem is ephemeral.

Configure database backups through Render Postgres or another durable backup service. The legacy `db:backup` command only supports MySQL and local filesystem output and is not part of the PostgreSQL schedule.

Payments remain disabled by default. Enable Stripe or IntaSend only after live credentials, webhook configuration, provider-supported currencies, and end-to-end payment/refund tests have been completed.

## Launch checks

- Set the production domain in `APP_URL` and verify TLS and secure cookies.
- Confirm database and SMTP are reachable from the service.
- Run the app's registration, booking, KYC upload/review, notification, admin, payment-webhook, and restore/recovery flows in a staging environment.
- Confirm the deployed commit and health probe, then review logs and provider alerts after launch.
