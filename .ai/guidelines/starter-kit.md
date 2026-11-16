# Project Conventions

These rules come from this project's starter kit and take precedence over the generic guidelines below when they differ.

## Checks

- Run `composer qa` before every commit. It applies Rector, applies Pint, runs Larastan at max level (including `tests/`), then runs the full test suite. Narrower checks such as `vendor/bin/pint --dirty` or a filtered test run are fine while you work, but they don't replace `composer qa`.
- `composer test` runs the full suite. `composer test:tia` runs only the tests affected by your changes with Pest's experimental Test Impact Analysis; it needs PCOV or Xdebug and a git repository with at least one commit. Use it for fast feedback, never as a replacement for `composer qa`.
- Commit `_ide_helper_models.php` after changing models or migrations: run `composer ide-helper` against a migrated database.

## Database

- PostgreSQL only. Don't add SQLite or MySQL support, and use PostgreSQL features freely.
- Tests use the app's database name with `_testing` appended, such as `laravel_testing`. `phpunit.xml` forces `APP_ENV=testing` and clears `DB_URL`; don't point tests at another database.

## Models

- There is no `User` model, users table or authentication. Don't reference `App\Models\User`, `auth()` or the `auth` middleware unless the task adds authentication.
- Models go in `app/Models` and factories in `database/factories`.

## Livewire

- Write Livewire components as classes in `app/Livewire` with their views in `resources/views/livewire`. `php artisan make:livewire` creates this form. Single-file and multi-file components are disabled, and `tests/ArchTest.php` fails on them.
- Full-page components use the `layouts::app` layout (`resources/views/layouts/app.blade.php`), which includes the page head partial. Register them with `Route::livewire('/path', App\Livewire\Page::class)`.
- The kit installs free Flux (`livewire/flux`). Use Flux Pro components only when `composer.json` requires `livewire/flux-pro`.

## Cache

- `config/cache.php` sets `serializable_classes => false`, so cached objects come back as `__PHP_Incomplete_Class`. Cache arrays and scalars, such as `->toArray()` or a list of IDs, never models or collections.

## Search

- Scout uses Typesense when `SCOUT_DRIVER=typesense` and the `collection` driver otherwise. Typesense doesn't support `scout:sync-index-settings`; define the schema and search parameters on the model with `typesenseCollectionSchema()` and `typesenseSearchParameters()`.

## Documentation

- An empty `search-docs` result doesn't prove an API is missing. Check the package source in `vendor/` before concluding that.
