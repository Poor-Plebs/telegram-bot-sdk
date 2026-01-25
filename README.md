# poor-plebs/package-template

[![CI](https://github.com/Poor-Plebs/package-template/actions/workflows/ci.yml/badge.svg)](https://github.com/Poor-Plebs/package-template/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Poor-Plebs/package-template/branch/main/graph/badge.svg)](https://codecov.io/gh/Poor-Plebs/package-template)

**[What is it for?](#what-is-it-for)** |
**[What are the requirements?](#what-are-the-requirements)** |
**[How to install it?](#how-to-install-it)** |
**[How to use it?](#how-to-use-it)** |
**[How to contribute?](#how-to-contribute)**

Put a short one or two sentence description of the package.

## What is it for?

Explain in detail here what this package is for.

## What are the requirements?

Explain here what the runtime requirements are, which extensions need to be
installed.

- PHP 8.4 or above

## How to install it?

Explain here how to install the package.

```bash
composer require poor-plebs/package-template
```

## How to use it?

Explain here how to use this package.

## How to contribute?

`poor-plebs/package-template` follows semantic versioning. Read more on
[semver.org][1].

Create issues to report problems or requests. Fork and create pull requests to
propose solutions and ideas. Always add a CHANGELOG.md entry in the unreleased
section.

### Development Setup

This template uses modern PHP tooling with strict quality standards:

- **Testing**: [Pest PHP](https://pestphp.com/) v4 with parallel execution
- **Static Analysis**: PHPStan at level `max` with strict and deprecation rules
- **Code Style**: PHP-CS-Fixer (PSR-12)
- **Coverage Requirements**: Minimum 80% code coverage and 80% type coverage

### Available Commands

```bash
composer test          # Run tests (parallel, no coverage)
composer coverage      # Run tests with coverage (min 80%)
composer type-coverage # Check type coverage (min 80%)
composer static        # Run PHPStan analysis
composer cs            # Check code style
composer csf           # Fix code style
composer ci            # Run full CI pipeline
```

### Architectural Tests

The template includes architectural tests in `tests/ArchTest.php` that enforce:
- Strict types declaration in all files
- Proper namespace conventions
- No debugging functions (`dd`, `dump`, `var_dump`, etc.)

### AI-Assisted Development

See [.github/copilot-instructions.md](.github/copilot-instructions.md) for
guidelines on AI-assisted contributions.

[1]: https://semver.org
