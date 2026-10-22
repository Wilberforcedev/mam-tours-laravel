# MAM Tours Production Readiness Remediation

This branch implements an initial production-hardening and operations foundation. It is **not yet certified for production**. Run migrations and tests in staging before merging.

## Implemented in this branch

### Booking availability and quotations
- Checks date/time overlap against pending, reserved, confirmed and in-use bookings.
- Locks the vehicle row during booking creation/reservation so concurrent requests for the same vehicle serialize on supported relational databases.
- Expired pending/reserved holds are ignored by availability checks; the scheduler expires old holds.
- Active maintenance records can block availability.
- Central pricing service supports configurable minimum/maximum rental days, default pickup/return times, weekend surcharge, weekly discount, additional-driver and child-seat charges, delivery fees, tax, deposit and currency.
- Booking quotes carry a pricing version and currency. Availability endpoint: `GET /api/availability?car_id=1&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`.

### Payments and ledger
- Stripe PaymentIntents are gated behind explicit configuration and validate booking ID, amount and currency before a booking is marked paid.
- Confirmed Stripe payments and Stripe refunds are recorded in the financial transaction ledger.
- Finance staff can record cash/bank/manual transactions with an idempotency key and reference; transaction records are append-only through the provided API.
- Placeholder API payment endpoints return HTTP 501 rather than false success.
- Refund requests are available for canceled, paid bookings and use an idempotency key.

### Identity-document privacy
- New KYC and booking identity documents are stored on a private disk, not the public storage disk.
- Existing KYC and booking document files are moved out of public storage by data migrations when their legacy files exist.
- Admin document-download routes use the private disk; KYC review routes remain admin-protected.
- Identity numbers and document paths are excluded from the booking audit payload.

### Fleet, staff and reporting
- Added maintenance/service/accident/inspection records, costs, scheduled dates, insurance/licence expiry fields and a rental-block flag.
- Added structured pickup/return odometer, fuel percentage and condition-note fields.
- Daily scheduler creates in-app fleet reminders for upcoming maintenance and insurance/licence expiry.
- Added staff roles `booking`, `finance`, and `fleet` alongside `admin` and `customer`, with role-specific API routes and an admin role-change endpoint.
- Added finance ledger summaries, booking reports and a management summary for utilization, paid booking value, overdue rentals, outstanding balances and maintenance costs.

## Important limitations / deployment gates

1. **Real payment credentials and providers:** Stripe is disabled by default. Configure `STRIPE_ENABLED=true`, `STRIPE_CURRENCY`, and secret keys only after verifying the live Stripe account supports the selected currency and completing test-mode end-to-end checks. The amount adapter currently uses two-decimal API units for UGX/USD; do not select other currencies without implementing and testing their minor-unit rules.
2. **Mobile money:** MTN and Airtel adapters are not implemented. Mobile money deliberately fails safely and must remain disabled until a real provider integration, request signing, status reconciliation, and webhook tests are completed.
3. **Refund scope:** automated provider refunds currently cover Stripe only. Cash/bank refunds must be recorded by finance after the actual transfer is verified. Deposit liability/release and partial-refund policies still require formal business rules.
4. **External notifications:** the existing email notification service logs messages in development mode; it is not proof of real email delivery. Configure and test the production mail/SMS provider before relying on reminders or receipts.
5. **Financial reconciliation:** ledger endpoints provide a foundation, but bank statement imports, provider settlement reconciliation, deposit accounting, and a full accounting export still need implementation.
6. **Fleet operations:** the maintenance record is a simple operational record; no third-party insurance/licence registry integration or automated maintenance-cost profitability accounting is included.
7. **Testing:** this environment has not run PHP lint, static analysis, migrations, or the test suite. Add/execute tests for concurrent overlapping bookings, expiry cleanup, role authorization, private document access, payment amount/currency mismatches, duplicate webhooks, refunds, and reports.
8. **Production safety:** back up the database and public/private storage before running the document migration. Keep all secrets in the hosting platform's secret manager. Ensure the Laravel scheduler is running every minute and queue workers are running where required.

## Key routes

- `GET /api/availability` — date-based availability and quotation.
- `GET|POST|PUT /api/operations/maintenance` — fleet maintenance (fleet/admin).
- `GET /api/operations/reports/financial-summary` — ledger totals grouped by currency/type/status (finance/admin).
- `GET /api/operations/reports/bookings` — booking report (finance/admin).
- `GET /api/operations/reports/management-summary` — utilization, overdue rentals, outstanding balances and expiry/service reminders (finance/admin).
- `GET /api/operations/transactions` — search the ledger (finance/admin).
- `POST /api/operations/transactions` — append a cash/bank/manual ledger transaction (finance/admin).
- `GET /api/operations/transactions/{id}/receipt` — download a receipt for a completed transaction (finance/admin).
- `POST /api/operations/bookings/{id}/refund` — Stripe refund request for a canceled paid booking (finance/admin).
- `PUT /api/admin/users/{id}/role` — assign an approved staff/customer role (admin).

Do not accept live payments or merge into production until staging tests pass and the provider integrations above are configured.
