# Contributing Guide

Thank you for considering contributing to `laravel-status-transition`. 
This is my first ever package, so it could went not as expected in some aspects,
so I respect if there is any contributions coming from you guys,
Amateurs are welcome, I'm using this library as a starter learning myself,
This document covers everything you need to get started.

## Code of Conduct

Be respectful and constructive in all interactions. Discriminatory, harassing, or dismissive behaviour will not be tolerated.

## How to Contribute

### Reporting Bugs

Before opening an issue, search existing issues to avoid duplicates. When filing a bug report, include:

- PHP version
- Laravel version
- Package version
- Steps to reproduce
- Expected vs actual behaviour
- Relevant stack trace or test output

### Suggesting Features

Open an issue with the label `enhancement`. Describe the use case clearly — a feature is more likely to be accepted if it solves a real, concrete problem rather than a hypothetical one.

### Submitting a Pull Request

1. Fork the repository and create your branch from `main`:

   ```bash
   git checkout -b feat/your-feature-name
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Make your changes. Follow the coding standards below.

4. Add or update tests to cover your changes. PRs without tests will not be merged.

5. Run the full test suite and make sure everything passes:

   ```bash
   vendor/bin/phpunit --testdox
   ```

6. Commit using a clear, descriptive message (see commit conventions below).

7. Push your branch and open a pull request against `main`.

## Coding Standards

- Follow **PSR-12** coding style.
- All public methods must have **PHPDoc blocks** with `@param`, `@return`, and `@throws` where applicable.
- Use **typed properties and return types** — do not omit them.
- Keep methods focused — a method should do one thing.
- Do not introduce new dependencies without prior discussion in an issue.

## Commit Conventions

Use the following prefixes to keep the history readable:

| Prefix | When to use |
|--------|-------------|
| `feat:` | New feature or behaviour |
| `fix:` | Bug fix |
| `test:` | Adding or updating tests |
| `docs:` | Documentation changes only |
| `refactor:` | Code change that is neither a fix nor a feature |
| `chore:` | Maintenance tasks (dependency updates, CI config) |

Examples:

```
feat: add canTransitionTo() helper method
fix: correct config key in shouldRecordHistory()
test: add it_does_not_mix_histories_between_models
docs: update README with custom status column example
chore: add PHP 8.4 to CI matrix
```

## Writing Tests

- Place tests under `tests/` in a folder that mirrors the feature being tested.
- Each test class extends `Rizalsaja\LaravelStatusTransition\Tests\TestCase`.
- Use the `/** @test */` docblock on every test method.
- Test method names must describe the behaviour being verified, not the implementation detail.
- Use fixture models from `tests/Fixtures/` — do not reference real application models.
- Do not rely on `created_at` for ordering assertions — use `id` instead. SQLite in-memory timestamps are not reliable within a single test run.

### Running Tests Against a Specific Laravel Version

```bash
composer require \
  "laravel/framework:^11.0" \
  "orchestra/testbench:^9.0" \
  --no-update

composer update
vendor/bin/phpunit --testdox
```

## Project Structure

```
laravel-status-transition/
├── src/
│   ├── Exceptions/
│   │   └── InvalidStatusTransitionException.php
│   ├── Models/
│   │   └── StatusHistory.php
│   ├── Traits/
│   │   └── HasStatus.php
│   └── LaravelStatusTransitionServiceProvider.php
├── database/
│   └── migrations/
│       └── create_status_histories_table.php
├── config/
│   └── laravel-status-transition.php
└── tests/
    ├── Fixtures/
    │   ├── migrations/
    │   ├── FoodOrder.php
    │   └── Order.php
    ├── Model/
    │   └── StatusHistoryModelTest.php
    ├── HasStatusTest.php
    └── TestCase.php
```

## Branch Strategy

| Branch | Purpose |
|--------|---------|
| `main` | Stable, released code |
| `develop` | Active development — target PRs here |
| `feat/*` | New features |
| `fix/*` | Bug fixes |
| `docs/*` | Documentation only |

## Versioning

This package follows [Semantic Versioning](https://semver.org):

- **PATCH** (`1.0.x`) — backwards-compatible bug fixes
- **MINOR** (`1.x.0`) — new backwards-compatible features
- **MAJOR** (`x.0.0`) — breaking changes

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE.md).
