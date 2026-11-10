#  Laravel Config Outdated
An Artisan command to find out when your published config files are out of date.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jackbayliss/laravel-config-outdated.svg?style=flat-square)](https://packagist.org/packages/jackbayliss/laravel-config-outdated)
[![GitHub Tests Action Status](https://github.com/jackbayliss/laravel-config-outdated/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jackbayliss/laravel-config-outdated/actions/workflows/run-tests.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/jackbayliss/laravel-config-outdated/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jackbayliss/laravel-config-outdated/actions/workflows/fix-php-code-style-issues.yml?query=branch%3Amain)

Once you've published a package's config file, you never hear about new options the package adds or old ones it drops. This compares your published config files against the originals and tells you which keys are missing or no longer used. It never changes your files.

## Requirements

Laravel 13+.

## Installation

You can install the package via composer:

```bash
composer require jackbayliss/laravel-config-outdated --dev
```

## Usage

```bash
php artisan config:outdated
```

```
  config/database.php ......................................... 2 missing
  database.connections.sqlite.busy_timeout ...................... missing
  database.connections.sqlite.journal_mode ...................... missing

  config/mail.php ................................. 1 missing, 1 unknown
  mail.mailers.smtp.scheme ...................................... missing
  mail.mailers.smtp.encryption .................................. unknown
```

This checks package config files published with `vendor:publish`, as well as the framework config files published with `config:publish`.

A `missing` key is in the original config file but not yours. An `unknown` key is in yours but not the original.

| Option | Description |
| --- | --- |
| `file` | Only check the given config file, e.g. `horizon`. |
| `--json` | Output the outdated config files as JSON. |
| `--fail` | Exit with a non-zero code if any config file is outdated, handy for CI. |

Only keys are compared, not values. Things like `database.connections` and `filesystems.disks` are yours to add to or remove from, so deleting a connection won't show up, but a new option missing from a connection you kept will.

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
