# MAM Tours

MAM Tours is a Laravel car-rental and fleet-management application. It supports customer registration, vehicle bookings, booking holds, fleet maintenance records, KYC review, invoices, and configurable payment integrations.

## Current production status

The repository includes a Render Docker deployment blueprint, PostgreSQL configuration, persistent customer upload storage, and supervised scheduler and queue worker processes. A successful deployment still does not establish production readiness:

- The application uses Laravel 8, which is end of life. Upgrade Laravel and its dependencies to a supported release before handling production customer data.
- Keep the Render service on one instance while it uses the attached persistent disk and in-container scheduler/worker.
- Configure transactional SMTP and a production PostgreSQL plan with suitable backups.
- Payments are disabled by default. Enable a provider only after live credentials, webhooks, supported currencies, and end-to-end payment and refund checks are complete.
- Run the full staging checklist in [RENDER_DEPLOYMENT.md](RENDER_DEPLOYMENT.md) and confirm the GitHub Actions checks pass. Render's Blueprint is set to deploy only after checks pass.

## Local development

Requirements: PHP 8.2, Composer, Node.js/npm, and a supported local database (MySQL or PostgreSQL).

```bash
git clone https://github.com/Wilberforcedev/mam-tours-laravel.git
cd mam-tours-laravel
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Set the database connection and credentials in `.env`, then run:

```bash
php artisan migrate
npm run build
php artisan serve
```

The app is available at http://127.0.0.1:8000. Seeders are optional and should be used only with a disposable development database.

## Deploying to Render

Use the repository's [render.yaml](render.yaml) Blueprint. It creates a Docker web service and managed PostgreSQL database, mounts a persistent disk at `storage/app`, and runs migrations at container startup. Docker builds the PHP/Node dependencies and frontend assets; no separate Render build or start command is needed.

1. Review [RENDER_DEPLOYMENT.md](RENDER_DEPLOYMENT.md) before creating production resources.
2. Create a Render Blueprint for this repository and sync the Blueprint.
3. Provide SMTP secrets when prompted: `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_ADMIN_EMAIL`.
4. Choose a paid web plan and a PostgreSQL plan with backups appropriate for customer and booking data. The persistent disk requires a paid web service and the service must remain a single instance.
5. Back up and restore any existing customer uploads and KYC documents to the persistent disk before routing production traffic.
6. Create the first administrator using the hidden password prompt:
   ```bash
   php artisan admin:create "MAM Tours Admin" admin@example.com
   ```
7. Complete staging checks for booking, KYC access, mail delivery, queue processing, payment callbacks, backups, and recovery.

The Render container runs Apache, Laravel's scheduler, and a database-backed queue worker under Supervisor. Do not configure a second scheduler for this same service. Before scaling horizontally, move the scheduler and queue worker into dedicated services and replace the single attached disk with storage designed for shared access.

For setup details and launch checks, see [RENDER_DEPLOYMENT.md](RENDER_DEPLOYMENT.md) and [RENDER_QUICK_START.md](RENDER_QUICK_START.md).

## Payments and notifications

Stripe and IntaSend integrations are disabled by default. Keep them disabled until provider credentials and signed callback settings are stored as hosting secrets and tested end to end. Provider initiation is not proof of payment; verify provider-confirmed amount and currency before recording a payment as complete.

Use a transactional email provider for production mail. Do not commit credentials or put private KYC files under the public web directory.

## Tests and quality checks

Run available checks before submitting changes:

```bash
php artisan test
composer audit
./vendor/bin/phpstan analyse --memory-limit=2G
./vendor/bin/phpcs
```

Also test migrations and key booking, KYC, payment, and restore flows in staging. CI and staging checks must pass before production launch.

## First administrator

The seeder does not create an administrator account. Create one from a trusted application shell:

```bash
php artisan admin:create "MAM Tours Admin" admin@example.com
```

The password is entered through a hidden prompt. Do not use a demo or shared password.

## Main technologies

- Laravel 8 and PHP
- Vue.js 3 and Tailwind CSS
- PostgreSQL for the Render deployment; local development may use MySQL or PostgreSQL
- Stripe and IntaSend integrations, disabled by default

