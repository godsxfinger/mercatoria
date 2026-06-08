# Mercatoria

[![Build](https://img.shields.io/github/actions/workflow/status/godsxfinger/mercatoria/.github/workflows/laravel.yml?branch=main&style=flat-square)](https://github.com/godsxfinger/mercatoria/actions)
[![License](https://img.shields.io/github/license/godsxfinger/mercatoria?style=flat-square)](LICENSE.txt)
[![Issues](https://img.shields.io/github/issues/godsxfinger/mercatoria?style=flat-square)](https://github.com/godsxfinger/mercatoria/issues)
[![Repo size](https://img.shields.io/github/repo-size/godsxfinger/mercatoria?style=flat-square)](https://github.com/godsxfinger/mercatoria)

Mercatoria is a privacy-first, multi-vendor marketplace built with Laravel 11 and optional Monero payment integration. It is designed as a professional reference application for modern e-commerce workflows, vendor onboarding, dispute resolution, and privacy-conscious user experience.

## Overview

This project demonstrates a production-style marketplace architecture with:

- role-based accounts for buyers, vendors, and admins
- product catalog management, stock controls, and vendor delivery options
- single-vendor cart enforcement and secure checkout rules
- order lifecycle handling from payment through delivery, completion, dispute, and refund
- abstract payment gateway architecture with Monero adapter support
- encrypted messaging and challenge-based authentication
- admin moderation, analytics, and platform controls

## Highlights

- Laravel 11 application structure with clear services and policies
- Feature and unit tests for core behavior
- CI workflow for automated PHPUnit validation
- Professional repo metadata and contribution guidance
- MIT license for open-source distribution

## Technology Stack

- PHP 8.2+
- Laravel 11
- Blade templating with Vite frontend build
- MySQL-compatible database
- PHPUnit for automated tests
- GitHub Actions for CI

## Getting Started

### Prerequisites

- PHP 8.2 or later
- Composer
- Node.js 18+ and npm
- MySQL or compatible database

### Local Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Then visit `http://127.0.0.1:8000`.

### Development

```bash
npm run dev
```

### Running Tests

```bash
vendor/bin/phpunit
```

## Repository Structure

- `app/` — application logic, controllers, models, policies, services
- `config/` — Laravel and marketplace configuration
- `database/seeders/` — demo and production seeders
- `resources/views/` — Blade UI templates
- `routes/web.php` — application routes
- `.github/workflows/laravel.yml` — CI pipeline for PHP and MySQL testing

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening issues or pull requests.

## Security

If you discover a security issue, please follow the process described in `SECURITY.md`.

## License

This project is licensed under the MIT License. See `LICENSE.txt` for details.
