# MAM Tours

MAM Tours is a Laravel application for managing vehicle rentals in Uganda. It includes a customer-facing web application, an admin dashboard, and a JSON API for mobile or other clients.

## Features

- Browse and search the vehicle fleet.
- Register, sign in, manage a profile, and submit KYC documents.
- Request and manage bookings, with date-overlap checks and protected customer booking access.
- Manage vehicles, bookings, KYC reviews, and reports through admin tools.
- Accept card payments through Stripe when Stripe credentials are configured.
- Start an MTN or Airtel mobile-money payment. Initiation is simulated; a live provider integration is not included.
- Send email and SMS notifications when the relevant services are configured.
- Generate booking invoices as PDFs.

## Technology

- PHP 8.0 or later
- Laravel 8
- MySQL (recommended for development and production)
- Vue 3, Vite, and Tailwind CSS for frontend assets
- Laravel Sanctum for API tokens
- Stripe PHP SDK for card payments

## Requirements

Install PHP and Composer, a supported database, and Node.js with npm if you need to rebuild the frontend assets.

The PHP version must satisfy the constraint in `composer.json` (`^8.0`). Create a MySQL database and database user before running migrations.

## Local setup

Clone the repository and install dependencies:

```bash
git clone https://github.com/Wilberforcedev/mam-tours-laravel.git
cd mam-tours-laravel
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set the database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mam_tours
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Create the database first, then run migrations and the optional car catalog seeder:

```bash
php artisan migrate
php artisan db:seed
```

The seeder adds sample cars. It does not create an administrator or demo customer.

Install and build frontend assets when needed:

```bash
npm install
npm run build
```

For frontend development with Vite's development server, run `npm run dev` in a separate terminal.

Start the application:

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

If the app serves public files from Laravel's storage disk, create the public storage link:

```bash
php artisan storage:link
```

## Create an administrator

The database seeder does not create administrator accounts. Create one from a trusted shell using the interactive command:

```bash
php artisan admin:create "MAM Tours Admin" admin@example.com
```

The command prompts for the password without displaying it. Do not put production credentials in source control or use a shared demo administrator account.

## Configuration

Most settings are defined in `.env.example`. Copy that file to `.env` and provide values appropriate to your environment.

| Setting | Purpose |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Application environment and public URL |
| `DB_*` | Database connection |
| `MAIL_*` | Outgoing email |
| `STRIPE_KEY`, `STRIPE_SECRET` | Stripe publishable and secret keys |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook verification |
| `WEBHOOK_SECRET` | Shared HMAC secret for mobile-money callbacks |
| `TWILIO_*` or `AFRICAS_TALKING_*` | SMS provider credentials |
| `VITE_GOOGLE_MAPS_API_KEY` | Google Maps frontend integration |

Use Stripe test keys while testing and live keys only in a secured production environment. The Stripe integration charges in UGX. Never commit `.env`, API keys, signing secrets, or customer documents.

## Web application

Useful pages include:

- `/` — home page
- `/explore-uganda` — destination information
- `/register` and `/login` — customer accounts
- `/bookings` — customer booking interface
- `/profile` and `/kyc` — customer profile and verification
- `/dashboard` — customer dashboard
- `/admin` — administrator dashboard
- `/admin/kyc` — KYC review
- `/api/docs` — API documentation UI

## JSON API

API routes are prefixed with `/api`. The API specification is available at `GET /api/docs/spec`.

### Public routes

| Method | Route | Purpose |
| --- | --- | --- |
| `POST` | `/api/auth/login` | Sign in and receive a Sanctum token |
| `GET` | `/api/cars` | List cars |
| `GET` | `/api/cars/{id}` | View a car |
| `GET` | `/api/v2/cars/search` | Search cars, including date availability |
| `POST` | `/api/bookings` | Submit a booking request |
| `GET` | `/api/health` | API health information |

### Authenticated routes

Send the token returned by login in the request header:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/api/user` | View the signed-in user |
| `POST` | `/api/auth/logout` | Revoke the current token |
| `GET` | `/api/bookings` | List bookings (customers see their own; admins can see all) |
| `GET` | `/api/bookings/{id}` | View an accessible booking |
| `POST` | `/api/bookings/reserve` | Reserve a car for selected dates |
| `POST` | `/api/bookings/{id}/confirm` | Confirm an accessible booking |
| `POST` | `/api/bookings/{id}/cancel` | Cancel an accessible booking |
| `POST` | `/api/payments/bookings/{booking}/stripe/intent` | Create a Stripe payment intent |
| `POST` | `/api/payments/bookings/{booking}/stripe/confirm` | Verify a Stripe payment intent |
| `POST` | `/api/payments/bookings/{booking}/mobile-money` | Start simulated mobile-money payment |
| `POST` | `/api/v2/kyc/submit` | Submit KYC documents |

Stripe confirmation requires a succeeded intent whose currency, amount, and booking ID match the booking. The payment is marked complete only after those checks pass. Booking access is scoped to its customer; administrators use the admin-protected routes for fleet and booking management.

### Administrator routes

Administrator routes require a Sanctum token belonging to an admin account.

| Method | Route | Purpose |
| --- | --- | --- |
| `POST`, `PUT`, `DELETE` | `/api/cars...` | Manage cars |
| `POST` | `/api/bookings/{id}/check-out` | Check out a vehicle |
| `PUT` | `/api/bookings/{id}/return` | Record a vehicle return |
| `POST` | `/api/payments/bookings/{booking}/refund` | Request a provider refund |
| `GET` | `/api/reports/...` | Download booking or fleet reports |
| `/api/v2/admin/kyc...` | — | Review KYC submissions |

The former public payment success stubs and `POST /api/test-upload` endpoint have been removed.

## Payments and callbacks

Stripe card payments require valid Stripe keys. Use the authenticated booking payment routes above; do not treat a client response as proof of payment. The API verifies payment intent status, currency, amount, and booking metadata before updating the booking.

MTN and Airtel payment initiation is currently simulated and does not contact a mobile-money provider. Do not present it as a completed or live payment integration.

The signed mobile-money callback endpoint is:

```
POST /webhooks/mobile-money
```

Configure the same long random `WEBHOOK_SECRET` with the trusted callback sender. Send the signature in `X-Webhook-Signature` as the lowercase hexadecimal HMAC-SHA256 of the exact raw request body. The JSON body must include `transaction_id` and a `status` of `pending`, `completed`, `failed`, or `cancelled`. Only a valid signed `completed` callback for a pending transaction can complete the payment. Invalid or unsigned callbacks are rejected.

## Background work and operations

For scheduled reminders, configure Laravel's scheduler in the server crontab:

```cron
* * * * * cd /path/to/mam-tours-laravel && php artisan schedule:run >> /dev/null 2>&1
```

For production, configure a queue worker if `QUEUE_CONNECTION` uses a queued driver. Use HTTPS, set `APP_DEBUG=false`, use a production `APP_KEY`, restrict database and storage access, and configure mail, payment, and callback secrets outside the repository.

Deployment guides in this repository include `PRODUCTION_SETUP.md`, `PRE_DEPLOYMENT_CHECKLIST.md`, `RENDER_DEPLOYMENT.md`, `RENDER_QUICK_START.md`, and `RAILWAY_DEPLOYMENT.md`.

## Tests

Run the test suite with:

```bash
php artisan test
```

To run only feature tests:

```bash
php artisan test --testsuite=Feature
```

## Project layout

```
app/                 Application code, controllers, models, services
database/migrations/ Database schema
database/seeders/    Sample data seeders
resources/           Blade views and frontend source
routes/web.php       Browser routes
routes/api.php       JSON API routes
tests/               PHPUnit tests
```

## License

See the repository for license details.
