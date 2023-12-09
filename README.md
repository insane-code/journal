# Journal

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/insane/journal.svg?style=flat-square)](https://packagist.org/packages/insane/journal)


Journal is package that serves as a base for an accounting laravel apps powered by jetstream/inertia (CRM, Budgeting apps, stores etc). Journal provides the implementation for account ledger, double entry transactions, payees, account reconciliations, and more.

Actions, providers and policies are exported to give more flexibility.

## Installation
Once you have a project with jetstream + inertia scaffolding 

> Jetstream should only be installed into new Laravel applications. Attempting to install Jetstream into an existing Laravel application will result in unexpected behavior and issues.

```bash
php artisan jetstream:install inertia

php artisan jetstream:install inertia --teams
```

please read [jetstream documentation](https://jetstream.laravel.com/installation.html) for that steps if you're not familiar 
```bash
composer require insane/journal

journal:install
```

## Usage

Journal don't publish controllers or routes. Instead, Journal let you customize its behavior through actions.
During the installation process actions are published to your application's `app/Domains/Journal/Actions` directory

Action classes typically perform a single action and correspond to a single Journal feature, such as creating an account or deleting a category. You are free to customize these classes if you would like to tweak the backend behavior of Journal.

## Features
- [x] Account Ledger
- [x] Journal double entry
- [x] Products
- [x] Invoicing
- [x] Invoice payments registration (offline database registration not banking)
- [x] Reports


## Examples
Currently Journal power some of my apps:
- [loger(atmosphere)](https://github.com/jesusantguerrero/atmosphere)
- [icloan(prestapp)](https://github.com/jesusantguerrero/prestapp)
- Academia (comming soon)
- Neatlancer (comming soon)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Security

If you discover any security-related issues, please email jesusant.guerrero@gmail.com instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
