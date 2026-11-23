# Deploying MAM Tours to Render

This repository deploys as a Docker web service. Render's managed database is PostgreSQL, so the service uses Laravel's `pgsql` connection. PHP is not a native Render runtime.

## Before creating the Blueprint

1. Select a paid web service plan. The Blueprint uses a persistent disk and a pre-deploy command; both require a paid service plan.
2. Choose a production Render Postgres plan with backups and retention suitable for customer and booking data. The Blueprint intentionally does not select a paid database plan.
3. Prepare SMTP credentials from a transactional email provider. Mailtrap's sandbox credentials are not suitable for customer email.
4. Review the current Laravel version and dependency support policy before launch. This application still uses Laravel 8 and requires a supported-framework upgrade before a production launch.

## Create the Render resources

Create a Render Blueprint from this repository and sync `render.yaml`. It defines a Docker web service and a managed Postgres database. The Docker image includes the PDO drivers for PostgreSQL and MySQL; Render uses PostgreSQL.

The web service mounts a persistent disk at `storage/app`. This preserves public uploads and private KYC files across deploys and restarts. The disk is attached to a single service instance, so do not scale the web service horizontally until storage is migrated to object storage and the app's S3 adapter is installed and tested.

On the first Blueprint sync, provide these secret environment values:

- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_ADMIN_EMAIL`

For an existing Render service, add the new values manually in its Environment settings; Render does not prompt for `sync: false` values on later Blueprint syncs.

## Runtime behavior and required services

The web service has a health probe at `/ping`. Database migrations run in the pre-deploy phase, not on every web process start. The KYC disk is private and is not linked under the public web root. Back up legacy uploaded files before the first deployment and restore them to the persistent disk; Render's default filesystem is ephemeral.

Set up a separate scheduled task to run `php artisan schedule:run` every minute. The Laravel schedule sends booking reminders, expires booking holds, and sends fleet reminders. Configure database backups through Render Postgres or another durable backup service; the legacy `db:backup` command only supports MySQL and local filesystem output.

The queue is currently synchronous. This is operational for low traffic but email/SMS and other queued work can increase request latency. Before scaling, provision a persistent queue backend and a worker running `php artisan queue:work --sleep=3 --tries=3 --timeout=90`.

Payments remain disabled by default. Enable Stripe or IntaSend only after live credentials, webhook configuration, provider-supported currencies, and end-to-end payment/refund tests have been completed.

## Launch checks

- Set the production domain in `APP_URL` and verify TLS and secure cookies.
- Confirm database and SMTP are reachable from the service.
- Run the app's registration, booking, KYC upload/review, notification, admin, payment-webhook, and restore/recovery flows in a staging environment.
- Confirm the deployed commit and health probe, then review logs and provider alerts after launch.
