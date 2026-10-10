# amarenkov/laravel-mutable-content-tasks

[![tests](https://github.com/amarenkov/laravel-mutable-content-tasks/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-tasks/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-tasks)](https://packagist.org/packages/amarenkov/laravel-mutable-content-tasks)
![coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)

Task domain for [`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content):
tasks, their participants and time tracking, with services and events to build any interface on.

> Work in progress. Nothing is released yet.

## Scope

- Models with mutable fields, so an application adds its own task fields without migrations.
- Services and events: creating and changing tasks, assigning participants, starting and pausing
  time tracking. Every change lands in the core change log with its author.
- **No user interface.** Screens, bots and notifications channels belong to the application; the
  package does not depend on any UI kit, so it fits any project built on laravel-mutable-content.

## Requirements

- PHP 8.4
- Laravel 13
- PostgreSQL or MariaDB 10.7+, as required by `amarenkov/laravel-mutable-content`

## Installation

```bash
composer require amarenkov/laravel-mutable-content-tasks
```

## Translations

Strings are in `lang/{en,ru}/tasks.php` under the `mutable-content-tasks` namespace. Publish them
to override:

```bash
php artisan vendor:publish --tag=mutable-content-tasks-lang
```

## License

MIT. See [LICENSE](LICENSE).
