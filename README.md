# Mercatoria

Mercatoria is a privacy-first multi-vendor marketplace built with Laravel 11. It demonstrates vendor onboarding, product catalogs, cart and checkout rules, order lifecycle management, crypto payment integration, disputes, admin moderation, and privacy-oriented encrypted messaging in a portfolio-safe way.

## Project Overview

The application models a marketplace where buyers can browse vendors, build a single-vendor cart, create orders, track fulfillment, message vendors, and raise disputes. Vendors can manage listings, view sales, and receive payouts through an abstract payment layer. Admins can review users, products, disputes, and platform activity.

## Why I Built This

Mercatoria is designed as a realistic Laravel case study: complex enough to show architecture decisions, but framed around legitimate marketplace workflows, privacy-conscious authentication, and maintainable service boundaries.

## Tech Stack

- Laravel 11, PHP 8.3, Blade, Vite
- MySQL-compatible schema
- Laravel authorization policies and middleware
- Service/action classes for order and payment workflows
- PHPUnit feature and unit tests
- Optional Monero wallet RPC adapter behind a payment interface

## Core Features

- Multi-role accounts for buyers, vendors, and admins
- Product types for digital delivery, cargo shipping, and local pickup
- Single-vendor cart enforcement
- Checkout stock validation
- Order creation, payment tracking, fulfillment, completion, cancellation, refund, and dispute handling
- Privacy-oriented encrypted messaging
- Public-key authentication challenge support
- Admin moderation and marketplace statistics

## Buyer Workflow

Buyers register, browse products, add items from one vendor at a time, choose delivery or pickup options, and create an order. After payment is detected, the buyer can track fulfillment, complete the order, contact the vendor, or open a dispute when appropriate.

## Vendor Workflow

Vendors create and update product listings, define delivery options, manage stock, review sales, mark orders as sent, and receive released payments when orders complete.

## Admin Workflow

Admins can manage users, products, categories, platform messages, disputes, and marketplace statistics. Dispute tools allow admins to resolve outcomes while preserving order lifecycle rules.

## Order Lifecycle

Mercatoria uses explicit order statuses:

- `waiting_payment`
- `payment_received`
- `product_sent`
- `completed`
- `cancelled`
- `disputed`
- `refunded`

Allowed transitions are centralized in `OrderStateService`, so invalid transitions throw a clear exception instead of silently mutating order state.

## Payment Architecture

Payment behavior is accessed through `PaymentGateway`, which defines:

```php
createPaymentAddress(Order $order): PaymentRequest
checkPaymentStatus(Order $order): PaymentStatus
refund(Order $order): RefundResult
```

`MoneroPaymentGateway` is the current provider implementation. Controllers depend on the interface, so another provider can be added later without rewriting checkout or order controllers.

## Security Decisions

- `.env`, wallet credentials, and runtime logs are ignored
- Public-key authentication challenge logic is isolated in a service
- Messaging content is encrypted at rest by the model layer
- Authorization policies cover products, orders, disputes, messages, and vendor profiles
- Tests mock payment behavior and do not call real wallet RPC endpoints

## Database Design

The database preserves existing compatibility keys while improving source conventions. The legacy product type value `deaddrop` remains stored for compatibility, but the UI presents it as local pickup. The conventional `Order` model is available, with `Orders` kept as a deprecated alias to avoid abrupt breakage.

## Testing Strategy

The test suite covers brand smoke checks, product validation, cart stock behavior, order state transitions, payment abstractions, authorization boundaries, demo-safe messaging access rules, and basic auth/middleware flows. Payment tests use mocked gateways so local and CI runs do not require wallet RPC.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Demo Accounts

The demo seeder creates predictable, fake-only accounts:

- Admin: `demo_admin` / `Password!123`
- Vendor: `demo_vendor` / `Password!123`
- Buyer: `demo_buyer` / `Password!123`

These accounts are for local development only. They do not include real wallet credentials, secrets, or RPC values.

## Screenshots

Screenshots can be added from a local seeded run after final UI review.

## Lessons Learned

This project shows how a Laravel app can evolve from feature-heavy controllers into clearer model, policy, service, and action boundaries without a rewrite. The main takeaway is that compatibility and professionalism can coexist: legacy database values can be preserved while the public product story, naming, tests, and architecture become cleaner.

## License

See `LICENSE.txt`.
