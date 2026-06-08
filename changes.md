# Changelog

This file tracks meaningful product, security, architecture, and operational changes for Mercatoria.

## How To Update

- Add newest entries at the top.
- Use one section per date.
- Keep entries concise and outcome-focused.
- Include affected components and validation when useful.
- Do not include real secrets, wallet paths, local credentials, or machine-specific commands.

---

## 2026-06-08

### Laravel Model Convention Cleanup

- Added conventional `App\Models\Order` model.
- Kept `App\Models\Orders` as a deprecated compatibility alias to avoid breaking legacy references abruptly.
- Moved nested model classes into standalone files:
  - `app/Models/OrderItem.php`
  - `app/Models/DisputeMessage.php`
- Updated first-party imports/usages to prefer `Order`.
- Validation: PHP lint, Composer autoload dump, route list, and test suite.

### Order Actions And Service Layer

- Added focused order action classes:
  - `CreateOrder`
  - `CancelExpiredOrder`
  - `CompleteOrder`
  - `RefundBuyer`
  - `ReleaseVendorPayment`
- Added `CartPricingService` to centralize subtotal, commission, and total calculations.
- Refactored order controller paths to call actions/services for order creation, completion, payment checks, and refunds.
- Reduced controller responsibility toward validation, orchestration, and redirects.

### Payment Gateway Abstraction

- Added `PaymentGateway` interface with:
  - `createPaymentAddress(Order $order): PaymentRequest`
  - `checkPaymentStatus(Order $order): PaymentStatus`
  - `refund(Order $order): RefundResult`
- Added payment DTOs:
  - `PaymentRequest`
  - `PaymentStatus`
  - `RefundResult`
- Added `MoneroPaymentGateway` as the current provider implementation.
- Bound `PaymentGateway` to `MoneroPaymentGateway` in `AppServiceProvider`.
- Tests use a mocked gateway and do not call real wallet RPC.

### Explicit Order Lifecycle

- Added `OrderStateService` with explicit allowed transitions:
  - `waiting_payment -> payment_received`
  - `waiting_payment -> cancelled`
  - `payment_received -> product_sent`
  - `payment_received -> refunded`
  - `product_sent -> completed`
  - `product_sent -> disputed`
  - `disputed -> completed`
  - `disputed -> refunded`
- Added `InvalidOrderTransitionException` for clear lifecycle failures.
- Added `refunded` as an explicit order status.
- Validation: unit/feature coverage for valid and invalid transitions.

### Authorization Policies

- Added and registered policies for:
  - `ProductPolicy`
  - `OrderPolicy`
  - `DisputePolicy`
  - `MessagePolicy`
  - `VendorPolicy`
- Policies centralize ownership/admin/vendor access checks and reduce scattered authorization logic.

### Public-Key Challenge Service

- Added `PublicKeyChallengeService` for public-key authentication challenge generation and encryption.
- Updated the auth controller to use the service for the main 2FA challenge path.
- Kept legacy helper methods in place for compatibility during this refactor pass.

### Demo Seed Data

- Added `DemoSeeder` with fake-only portfolio data:
  - admin user
  - vendor user
  - buyer user
  - categories
  - sample digital, cargo, and local pickup products
  - sample order
  - sample dispute and dispute messages
- Demo credentials are documented for local development only.
- No real wallet credentials, real secrets, or live RPC values are included.
- Validation: `php artisan db:seed --class=DemoSeeder`.

### Repository Hygiene

- Ensured `.env` remains ignored and untracked.
- Added runtime log ignore rules:
  - `*.log`
  - `monero-wallet-cli.log`
  - `monero-wallet-rpc.log`
  - `storage/logs/*.log`
- Removed local runtime log files from the workspace.
- Updated dependency locks while staying on Laravel 11.
- Composer audit has one documented ignored Laravel email-rule advisory because Mercatoria does not collect email addresses or use Laravel email validation.
- npm audit reports zero vulnerabilities.

### Automated Tests

- Replaced scaffold examples with meaningful tests.
- Added coverage for:
  - registration/login
  - banned user login block
  - admin middleware
  - vendor middleware
  - vendor product creation
  - single-vendor cart enforcement
  - stock validation
  - order creation
  - valid order transitions
  - invalid transition rejection
  - payment received flow with mocked `PaymentGateway`
  - refund flow with mocked `PaymentGateway`
  - dispute creation
  - message authorization
  - Mercatoria brand smoke checks
  - public canary/public-key document availability
- Validation: `php artisan test` passes with 18 tests and 50 assertions.

### Build And Runtime Validation

- `composer dump-autoload`: passed.
- `php artisan route:list --except-vendor`: passed with 149 routes.
- `php artisan test`: passed.
- `npm run build`: passed, including design-system guard.
- `npm audit --audit-level=moderate`: passed.
- `composer audit`: passed with the documented ignored Laravel advisory.

---

## 2026-03-08

### Dashboard Access And Host Header Hardening

- Restricted named dashboard access so sensitive dashboard analytics are available only to the owner or an admin.
- Preserved public profile access while hiding private metrics from non-owners.
- Added warning/informational logs for unauthorized dashboard attempts and public profile views.
- Enabled trusted host validation derived from `APP_URL`.
- Hardened proxy handling by removing forwarded-host trust.
- Affected components: `DashboardController`, `Kernel`, `TrustProxies`, `dashboard.blade.php`.
- Validation: PHP lint and route/controller review.

### RPC Configuration Alignment

- Aligned wallet RPC configuration with environment-driven host, port, SSL, username, and password settings.
- Fixed wallet RPC constructor usage in payout/refund paths.
- Updated operator docs and `.env.example` with placeholder-only RPC values.
- Affected components: `config/monero.php`, order/vendor/admin payment paths, RPC setup docs.
- Security note: documentation intentionally avoids real wallet secrets or machine-specific commands.

---

## 2026-02-28

### Upload And Private Media Hardening

- Added image dimension and pixel-count checks before image processing to reduce decompression-bomb risk.
- Hardened private image responses with stricter filename validation, MIME verification, and `nosniff`/private cache headers.
- Affected upload paths include profile pictures, vendor products, admin product edits, and vendor applications.

### Authentication And Password Reset Hardening

- Added rate limiting to auth and password reset flows.
- Reworked password reset tokens into selector/verifier format for targeted lookup and safer verification.
- Improved secret phrase storage with encrypted model accessors/mutators and backward-compatible reads.
- Affected components: `AuthController`, `SecretPhrase`, auth routes.

### Admin User And Vendor Lists

- Added admin search by username or UUID.
- Added sortable columns, status badges, page-size controls, and pagination metadata.
- Improved table hierarchy, row density, hover states, and empty states.
- Affected components: `AdminController`, `resources/views/admin/users/list.blade.php`, `public/css/styles.css`.

### Orders Index UX And Filtering

- Added server-side order filtering, date ranges, sorting, page-size controls, and query persistence.
- Reworked order rows with clearer status badges, compact identifiers, totals, and pagination metadata.
- Affected components: `OrdersController`, `resources/views/orders/index.blade.php`, `public/css/styles.css`.

### Admin Metrics Fixes

- Fixed pending support count to include only actionable tickets.
- Fixed pending vendor application count to include only paid submitted applications still waiting for review.
- Affected component: `AdminController`.

### Featured Products And Home Page UX

- Improved featured/sponsored product hierarchy with clearer badges, stronger CTAs, trust signals, hover states, and card depth.
- Affected components: `resources/views/home.blade.php`, `public/css/styles.css`.

### Sidebar And Shared UI Polish

- Refined sidebar hierarchy, active states, hover behavior, spacing, and mobile drawer styling.
- Aligned settings, support, notifications, auth, CAPTCHA, and admin pages with shared Mercatoria design tokens.
- Reduced legacy hardcoded colors and page-specific visual drift.
- Affected components: Blade views and `public/css/styles.css`.

### Development And Installation Docs

- Added clearer local setup guidance for Laravel, Vite, queue workers, and optional payment RPC integration.
- Added reminders to keep runtime credentials and wallet files outside source control.
- Affected docs: `README.md`, `docs/INSTALLATION.md`, `docs/CONNECTING-MONERO-RPC.md`.
