# Production Readiness Remediation

This branch contains an initial safety pass for MAM Tours. It is not a production certification.

## Changes in this branch

- Booking list results are scoped to the authenticated user's bookings; administrators can list all bookings.
- Booking detail responses are restricted to the owner or an administrator. Non-owned bookings return 404 to avoid confirming their existence.
- Booking confirmation is restricted to administrators.
- Booking creation responses no longer return the full booking model, which can include identity document paths and identity fields.
- Audit logging no longer records identity numbers or document paths.
- Booking creation errors no longer expose exception details to API clients.
- Placeholder payment routes now return HTTP 501 and explicitly state that no payment or refund was processed.
- The unauthenticated test-upload endpoint has been removed.

## Important limitations still requiring implementation

1. **Date-based availability:** booking creation and reservation still need a transactional, concurrency-safe date-overlap check. Do not rely on the global `isAvailable` flag as the sole availability source.
2. **Pricing configuration:** minimum/maximum rental duration, pickup/return times, delivery fees, deposits, taxes, discounts, and additional-driver fees need centrally configured business rules and a versioned quotation.
3. **Payment provider integration:** mobile-money processing is simulated. Stripe currency and minor-unit handling must be configured explicitly, and the provider intent must be checked against the booking, expected amount, and currency before marking payment complete.
4. **Private identity-document storage:** KYC documents currently use the public disk in existing flows. Move them to private storage and authorize each document read before collecting real customer documents.
5. **Financial ledger:** introduce immutable payment/refund transactions and reconciliation rather than treating a booking's single payment JSON field as the ledger.
6. **Fleet operations:** maintenance blocks, servicing history, insurance/licence reminders, incident records, and pickup/return mileage and fuel need dedicated data models and workflows.
7. **Staff permissions:** replace broad admin-only access where appropriate with least-privilege booking, finance, and fleet roles; audit privileged changes.
8. **Reporting:** build revenue, cash collection, utilization, profitability, cancellation, overdue-balance, and maintenance reports from reconciled source records.
9. **Verification:** run migrations, PHP lint/static analysis, and the automated test suite; add tests for authorization, booking overlap/concurrency, payment amount/currency mismatches, webhook replay, refunds, and private document access.

## Deployment gate

Do not merge this branch into production or accept live payments until the remaining critical items above are addressed and tested in a staging environment. Keep real provider secrets in the hosting platform's secret manager, never in source control.
