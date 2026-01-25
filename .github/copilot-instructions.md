# Agent instructions for poor-plebs/package-template

## Project Overview

This is a framework-agnostic PHP package template designed for building reusable PHP libraries. It provides a modern development environment with strict coding standards, comprehensive testing, and automated quality checks.

## Directory Structure

```text
├── src/                  # Source code (PSR-4: PoorPlebs\PackageTemplate)
├── tests/                # Test files (Pest PHP)
│   ├── Pest.php          # Pest configuration
│   ├── ArchTest.php      # Architectural tests
│   └── *Test.php         # Feature/Unit tests
├── cache/                # Tool caches (gitignored)
├── vendor/               # Composer dependencies
├── .github/              # GitHub workflows and config
└── *.dist files          # Distribution config files
```

## Coding Standards

### PHP Requirements

- **PHP Version**: 8.4+
- **Strict Types**: All files MUST declare `strict_types=1`
- **Code Style**: PSR-12 enforced via PHP-CS-Fixer

### Namespace Conventions

- Source code: `PoorPlebs\PackageTemplate\*`
- Tests: `PoorPlebs\PackageTemplate\Tests\*`

### Forbidden Patterns

- No debugging functions: `dd()`, `dump()`, `var_dump()`, `print_r()`, `ray()`
- Enforced via architectural tests in `tests/ArchTest.php`

## Tooling Commands

Some commands use caching in the `/cache` directory for performance (for example, PHP-CS-Fixer and PHPStan).

### Code Quality

```bash
composer lint          # PHP syntax check (parallel)
composer cs            # Check code style (dry-run)
composer csf           # Fix code style
composer static        # PHPStan analysis (level max)
```

### Testing

```bash
composer test          # Run tests without coverage (parallel)
composer coverage      # Run tests with coverage (min 80%)
composer coverage-html # Generate HTML coverage report
composer type-coverage # Check type coverage (min 80%)
```

### Full CI Pipeline

```bash
composer ci            # Run all checks (lint, cs, static, coverage)
composer all           # Run lint, auto-fix code style, static analysis, and tests (no coverage)
composer coverage-clover # Run tests with coverage, output clover.xml + junit.xml (min 80%)
composer type-coverage # Run type coverage check (min 80%)
```

## Coverage Requirements

- **Code Coverage**: Minimum 80%
- **Type Coverage**: Minimum 80%
- Coverage is enforced in CI and via `--min=80` flag

## Testing with Pest

This template uses [Pest PHP](https://pestphp.com/) v4 for testing.

### Writing Tests

```php
<?php

declare(strict_types=1);

use PoorPlebs\PackageTemplate\YourClass;

covers(YourClass::class);

it('does something', function (): void {
    $instance = new YourClass();

    expect($instance->method())->toBe('expected');
});

test('another scenario', function (): void {
    // Test implementation
});
```

### Architectural Tests

Add constraints to `tests/ArchTest.php`:

```php
arch('classes follow naming convention')
    ->expect('PoorPlebs\PackageTemplate')
    ->classes()
    ->toHaveSuffix('Handler');
```

## Static Analysis

PHPStan runs at level `max` with additional rulesets:

- `phpstan-strict-rules` - Stricter type checking
- `phpstan-deprecation-rules` - Deprecation warnings
- `bleedingEdge.neon` - Experimental rules

## Contribution Guidelines

1. All code must pass `composer ci` before committing
2. Add tests for new functionality
3. Maintain 80%+ code and type coverage
4. Update CHANGELOG.md with changes
5. Follow existing code patterns and naming conventions

## File Excludes

The following are excluded from distribution packages:

- Development configs (`.php-cs-fixer.dist.php`, `phpstan.neon.dist`, etc.)
- Tests directory
- Cache directory
- Git/GitHub files (`.git`, `.github`, `.gitignore`, `.gitattributes`)
- Editor config (`.editorconfig`)
- Composer lockfile (`composer.lock`)
- Test config (`phpunit.xml.dist`)
- Changelog (`CHANGELOG.md`)
