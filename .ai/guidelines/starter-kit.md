# Project Conventions

These rules come from this project's starter kit and take precedence over the generic guidelines below when they differ.

## Checks

- Run `composer qa` before every commit. It applies Rector, applies Pint, runs Larastan at max level (including `tests/`), then runs the full test suite. Narrower checks such as `vendor/bin/pint --dirty` or a filtered test run are fine while you work, but they don't replace `composer qa`.
- `composer test` runs the full suite. `composer test:tia` runs only the tests affected by your changes with Pest's experimental Test Impact Analysis; it needs PCOV or Xdebug and a git repository with at least one commit, and `tests/Pest.php` names `main` as the default branch. Use it for fast feedback, never as a replacement for `composer qa`.
- Commit `_ide_helper_models.php` after changing models or migrations: run `composer ide-helper` against a migrated database.

## Database

- PostgreSQL only. Don't add SQLite or MySQL support, and use PostgreSQL features freely.
- Tests use the app's database name with `_testing` appended, such as `laravel_testing`, however you run them. `phpunit.xml` sets `APP_ENV=testing`, clears `DB_URL`, ignores a cached config and sets the cache, mail, queue, session, filesystem, broadcasting, search and Nightwatch drivers, over any exported or `.env` value; don't point tests at another database or service.

## Models

- There is no `User` model, users table or authentication. Don't reference `App\Models\User`, `auth()` or the `auth` middleware unless the task adds authentication.
- Models go in `app/Models` and factories in `database/factories`.

## Livewire

- Write Livewire components as classes in `app/Livewire` with their views in `resources/views/livewire`. `php artisan make:livewire` creates this form. Don't create single-file or multi-file components: Livewire can still render them, but `tests/ArchTest.php` fails on them.
- Full-page components use the `layouts::app` layout (`resources/views/layouts/app.blade.php`), which includes the page head partial. Register them with `Route::livewire('/path', App\Livewire\Page::class)`.
- The kit installs free Flux (`livewire/flux`). Use Flux Pro components only when `composer.json` requires `livewire/flux-pro`.

## Cache

- `config/cache.php` sets `serializable_classes => false`, so cached objects come back as `__PHP_Incomplete_Class`. Cache arrays and scalars, such as `->toArray()` or a list of IDs, never models or collections, even where a skill's example caches them. Tests use the `array` store, which keeps objects, so they won't catch this.

## Search

- Scout uses its `collection` driver unless `SCOUT_DRIVER` is set, and tests always use it. `SCOUT_DRIVER=database` searches the model's PostgreSQL table directly. Typesense isn't installed; add it only when the task asks for it, as the README's "Adding Typesense" section describes. Typesense doesn't support `php artisan scout:sync-index-settings`; define its schema on the model.

## Monitoring

- To redact more in Nightwatch, add to the defaults in `config/nightwatch.php` (`redact_payload_fields`, `redact_headers`) rather than replacing them.

## Documentation

- An empty `search-docs` result doesn't prove an API is missing. Check the package source in `vendor/` before concluding that.
