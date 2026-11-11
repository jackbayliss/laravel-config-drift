# Laravel Config Drift
Find new and extra options in your published Laravel config files after upgrading packages or the framework.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jackbayliss/laravel-config-drift.svg?style=flat-square)](https://packagist.org/packages/jackbayliss/laravel-config-drift)
[![GitHub Tests Action Status](https://github.com/jackbayliss/laravel-config-drift/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jackbayliss/laravel-config-drift/actions/workflows/run-tests.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/jackbayliss/laravel-config-drift/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jackbayliss/laravel-config-drift/actions/workflows/fix-php-code-style-issues.yml?query=branch%3Amain)

Once you've published a package's config file, you never hear about new options the package adds or old ones it drops. This compares your published config files against the originals and tells you which options are new and which are extra. It never changes your files.

## Requirements

PHP 8.3+ and Laravel 13+.

## Installation

You can install the package via composer:

```bash
composer require jackbayliss/laravel-config-drift --dev
```

## Usage

Check all of your published config files:

```bash
php artisan config:outdated
```

```
  config/database.php ............................................. 2 new
  database.connections.sqlite.busy_timeout .......................... new
  database.connections.sqlite.journal_mode .......................... new

  config/mail.php ......................................... 1 new, 1 extra
  mail.mailers.smtp.scheme .......................................... new
  mail.mailers.smtp.encryption ...................................... extra
```

- `new`: an option in the original config file that yours doesn't have.
- `extra`: an option in your config file that the original doesn't have.

Check a single config file by passing its name:

```bash
php artisan config:outdated database
php artisan config:outdated horizon
```

Fail your CI build when a config file is outdated:

```bash
php artisan config:outdated --fail
```

Output the results as JSON:

```bash
php artisan config:outdated --json
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jack Bayliss](https://github.com/jackbayliss)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
