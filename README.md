# Mercatoria

[![License](https://img.shields.io/github/license/godsxfinger/mercatoria?style=flat-square)](LICENSE.txt) [![GitHub issues](https://img.shields.io/github/issues/godsxfinger/mercatoria?style=flat-square)](https://github.com/godsxfinger/mercatoria/issues) [![GitHub repo size](https://img.shields.io/github/repo-size/godsxfinger/mercatoria?style=flat-square)](https://github.com/godsxfinger/mercatoria)

Mercatoria is a privacy-first, multi-vendor marketplace built with Laravel 11 and Monero-compatible payment integration. It is designed as a professional reference app for modern marketplace workflows, platform moderation, and secure user interactions.

## Key Features

- Multi-role system for buyers, vendors, and administrators
- Vendor-managed product catalog with stock and delivery controls
- Single-vendor cart enforcement and flexible checkout rules
- Order lifecycle management with payment, shipment, completion, dispute, and refund states
- Abstract payment gateway interface with a Monero implementation
- Privacy-oriented encrypted messaging and public-key challenge login support
- Admin moderation tools for products, users, disputes, and marketplace analytics

## Technology Stack

- Laravel 11, PHP 8.2+
- Blade templates, Vite frontend build, and modern JavaScript tooling
- MySQL-compatible database schema
- PHPUnit tests for feature and unit coverage
- Composer dependency management and npm frontend tooling

## Repository Metadata

- **Project:** Mercatoria
- **Owner:** godsxfinger
- **License:** MIT
- **Repository:** https://github.com/godsxfinger/mercatoria

## Getting Started

### Requirements

- PHP 8.2 or later
- Composer
- Node.js 18+ and npm
- MySQL or compatible database

### Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open the application at `http://127.0.0.1:8000`.

### Development

```bash
npm run dev
```

### Running Tests

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome. Please open issues for bugs and feature requests, and create pull requests for improvements.

## License

This project is licensed under the MIT License. See `LICENSE.txt` for details.
