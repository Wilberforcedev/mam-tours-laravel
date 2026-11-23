# Production deployment checklist

Use this checklist with [RENDER_DEPLOYMENT.md](RENDER_DEPLOYMENT.md). The deployment target is defined by [render.yaml](render.yaml).

## Release blockers

- [ ] Upgrade from Laravel 8 to a supported Laravel release and update dependencies.
- [ ] Confirm GitHub Actions checks pass for the exact commit intended for deployment.
- [ ] Complete staging tests and review open items in [the production readiness report](docs/PRODUCTION_READINESS_REMEDIATION.md).
- [ ] Select a paid Render web plan and a PostgreSQL plan with backups appropriate for customer and booking data.
- [ ] Confirm the business has approved payment, refund, deposit, privacy, and data-retention policies.

## Hosting and configuration

- [ ] Create the Render resources by syncing the repository Blueprint.
- [ ] Set `APP_URL` to the actual HTTPS domain.
- [ ] Add transactional SMTP secrets: `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_ADMIN_EMAIL`.
- [ ] Confirm `APP_ENV=production`, `APP_DEBUG=false`, generated `APP_KEY`, secure cookies, and PostgreSQL configuration.
- [ ] Keep the service at one instance while it uses the attached disk and in-container scheduler/worker.
- [ ] Configure durable database backups and test restoring one.
- [ ] Confirm log access, health checks, and an alert/incident contact.

## Data and privacy

- [ ] Back up the existing database, public uploads, and private KYC documents.
- [ ] Review the document-storage migration against a production-like copy.
- [ ] Restore existing files to the persistent disk at `storage/app`.
- [ ] Verify KYC files cannot be fetched from public URLs and only authorized staff can review/download them.
- [ ] Create the first administrator with `php artisan admin:create`; do not seed demo users or sample data in production.

## Staging acceptance

- [ ] Test registration, sign-in, password reset, roles, and admin access.
- [ ] Test simultaneous overlapping booking requests, booking holds, expiry, cancellation, and vehicle maintenance blocks.
- [ ] Test KYC upload, review, private download, and retention/deletion procedures.
- [ ] Test transactional email and scheduler-generated reminders.
- [ ] Test queue processing and failed-job handling.
- [ ] If payments are approved, test signed webhooks, amount/currency mismatch rejection, duplicate callbacks, refunds, and ledger reconciliation with provider test credentials.
- [ ] Test backup restoration and recovery steps.
- [ ] Review application and provider logs after a full staging run.

## Release

- [ ] Confirm every release blocker and staging acceptance item is complete.
- [ ] Verify CI passes on the exact release commit; Render deploys only after checks pass.
- [ ] Confirm the health endpoint responds and the deployed commit is correct.
- [ ] Monitor logs, queue status, database backups, and payment-provider events after launch.

Payments are disabled by default. Do not enable Stripe or IntaSend until live credentials, callback verification, currency support, and the staging payment/refund tests are complete. See [the Render guide](RENDER_DEPLOYMENT.md) for runtime limits and setup details.
