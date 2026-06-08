# Contributing to Mercatoria

Thank you for your interest in contributing to Mercatoria. This repository aims to be professional, secure, and easy to extend.

## How to Contribute

- Open an issue for bugs, feature requests, or documentation improvements.
- Fork the repository and create a topic branch for your work.
- Keep your changes small and focused.
- Add tests for any new behavior or bug fixes.
- Follow existing code style and naming conventions.

## Pull Request Process

1. Fork the repository and create a feature branch.
2. Make your changes and run the test suite.
3. Open a pull request targeting `main`.
4. Include a clear description of your change, why it is needed, and any testing performed.

## Issue Guidelines

- For bugs, include steps to reproduce, expected behavior, and actual behavior.
- For enhancement requests, explain the use case and desired outcome.

## Code Style

- Use PSR-12 conventions for PHP code.
- Keep controller actions focused and delegate business logic to service classes.
- Write readable commit messages.

## Testing

Run the existing test suite before submitting changes:

```bash
vendor/bin/phpunit
```

For JavaScript build or assets:

```bash
npm run build
```
