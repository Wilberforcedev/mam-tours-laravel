# Render quick start

This guide follows the repository's `render.yaml` Blueprint. It creates a Docker web service and managed PostgreSQL database. The Dockerfile builds the application; Render should use the Docker runtime without manual build or start commands.

## Before production

This application currently uses Laravel 8, which is end of life. Upgrade to a supported Laravel release and pass CI and staging checks before using it with production customer data.

The Blueprint attaches a persistent disk, which requires a paid web service and keeps the service to one instance. Select a PostgreSQL plan with backups and retention appropriate for customer and booking data. Prepare a transactional SMTP account.

## Create the Render resources

1. Push the repository to GitHub.
2. In Render, create a new Blueprint and select this repository and the `main` branch.
3. Review the resources from `render.yaml` and sync the Blueprint.
4. Choose a paid web service plan and a suitable managed PostgreSQL plan.
5. Set the SMTP secrets when prompted:
   - `MAIL_HOST`
   - `MAIL_USERNAME`
   - `MAIL_PASSWORD`
   - `MAIL_FROM_ADDRESS`
   - `MAIL_ADMIN_EMAIL`

The Blueprint sets production mode, generates `APP_KEY`, connects PostgreSQL through `DATABASE_URL`, enables secure session cookies, and uses database-backed sessions, cache, and queues. Payments stay disabled.

For an existing Render service, add missing secret values in its Environment settings. A later Blueprint sync may not prompt for values marked `sync: false`.

## Before sending traffic

1. Set `APP_URL` to the service's actual HTTPS domain if it differs from the value in `render.yaml`.
2. Back up existing uploads and private KYC files. Restore them to the persistent disk at `storage/app` before directing users to the new service. Check file permissions and verify that KYC files remain private.
3. Review the deployment logs. The container mounts storage, runs database migrations, then starts Apache, Laravel's scheduler, and a database queue worker under Supervisor.
4. Create the administrator from the service Shell:
   ```bash
   php artisan admin:create "Your Name" admin@example.com
   ```
   The command prompts for a password without displaying it. Do not seed sample data in production.
5. Run the full staging checks in [RENDER_DEPLOYMENT.md](RENDER_DEPLOYMENT.md), including registration, booking and booking expiry, KYC upload and review, mail, queue processing, payment callbacks if enabled, database backups, and recovery.
6. Confirm GitHub Actions checks pass and Render deploys the intended commit. The Blueprint is configured with `autoDeployTrigger: checksPass`.

## Runtime limits to account for

- Keep this service at one instance while it uses the attached disk and in-container scheduler/worker.
- Do not add a duplicate cron scheduler for this service. Laravel's scheduler already runs in the container.
- Before horizontal scaling, move queue and scheduler processes into separate services and use shared durable object storage for uploads.
- Configure database backups through Render Postgres or another durable backup service. The legacy backup command is MySQL-specific and writes to local storage.
- Keep Stripe and IntaSend disabled until live credentials, webhook signing, currency support, and full payment/refund checks are complete.
