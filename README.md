# This is my package db-restore

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sigasistemas/db-restore.svg?style=flat-square)](https://packagist.org/packages/sigasistemas/db-restore)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sigasistemas/db-restore/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sigasistemas/db-restore/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sigasistemas/db-restore/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sigasistemas/db-restore/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/sigasistemas/db-restore.svg?style=flat-square)](https://packagist.org/packages/sigasistemas/db-restore)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require sigasistemas/db-restore
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="db-restore-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="db-restore-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="db-restore-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$dbRestore = new Callcocam\DbRestore();
echo $dbRestore->echoPhrase('Hello, Sigasistemas!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [](https://github.com/sigasistemas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
