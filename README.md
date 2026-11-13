# Laravel Starter Kit

An opinionated Laravel starter kit built on the official [Laravel Livewire starter kit](https://github.com/laravel/livewire-starter-kit). It keeps everything that kit ships (authentication with Fortify, including two-factor and passkeys, profile and security settings, and the Flux UI layouts) and adds a strict quality toolchain, Resend for email, and Laravel Nightwatch.

## What's included

| Area | Package | Notes |
| --- | --- | --- |
| Framework | Laravel 13, Livewire 4 | From the official Livewire starter kit |
| UI | [Flux](https://fluxui.dev) (free edition), Tailwind CSS 4 | No Flux Pro license needed |
| Auth | Laravel Fortify | Login, registration, email verification, password reset, 2FA, passkeys |
| Testing | [Pest 5](https://pestphp.com) | Parallel, time-balanced sharding, Test Impact Analysis |
| Formatting | [Laravel Pint](https://laravel.com/docs/pint) | `laravel` preset, defaults only |
| Refactoring | [Rector](https://getrector.com) + [rector-laravel](https://github.com/driftingly/rector-laravel) + Pest's Rector plugin | See [Rector configuration](#rector-configuration) |
| Static analysis | [Larastan](https://github.com/larastan/larastan) | `level: max` |
| IDE support | [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) | Model docblocks are written to the models |
| Email | [Resend](https://resend.com) (`resend/resend-php`) | Default mailer in `config/mail.php` |
| Monitoring | [Laravel Nightwatch](https://nightwatch.laravel.com) | `config/nightwatch.php` published, disabled until you enable it |

## Requirements

- PHP 8.4 or newer (Pest 5 requires it)
- Composer 2
- Node.js 22 and npm
- To create a project with `laravel new --using`: the Laravel installer (tested with 5.33) and `npx`
- [PCOV](https://github.com/krakjoe/pcov) or Xdebug for Pest's Test Impact Analysis locally (tests still run without one)

## Getting started

Create a new project from this repository with the [Laravel installer](https://laravel.com/docs/installation#installing-php):

```bash
laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit
```

The kit is not published on Packagist, so pass the full GitHub URL; the installer then downloads the repository with `npx tiged`. It creates `.env`, generates the app key, creates the SQLite database and runs the migrations, and asks whether to install and build the frontend.

Or clone it and run the setup script, which installs dependencies, creates `.env` from `.env.example`, generates the app key, runs the migrations (SQLite by default) and builds the frontend:

```bash
git clone https://github.com/epexhq/laravel-starter-kit my-app
cd my-app
rm -rf .git && git init -b main
composer setup
```

Then start the app, queue worker, logs and Vite together:

```bash
composer dev
```

## Composer scripts

| Script | What it does |
| --- | --- |
| `composer qa` | Applies Rector, applies Pint, runs Larastan, then runs the tests. Run it before every commit. |
| `composer test` | Clears cached config and runs `pest --parallel` (with TIA locally). |
| `composer ci:check` | The read-only version of `qa`: `rector --dry-run`, `pint --test`, Larastan, tests. |
| `composer refactor` / `refactor:check` | Rector, applying changes or as a dry run. |
| `composer lint` / `lint:check` | Pint, fixing or checking. |
| `composer types:check` | Larastan at max level. |
| `composer ide-helper` | Regenerates `_ide_helper.php`, `.phpstorm.meta.php` and model docblocks, then runs Pint. Run it after adding models or migrations. |
| `composer setup` / `composer dev` | Same as the official starter kit. |

`_ide_helper.php` and `.phpstorm.meta.php` are git-ignored. `composer setup` generates them; run `composer ide-helper` to refresh them after `composer update` or schema changes. (They aren't regenerated in `post-update-cmd`, because ide-helper is a dev dependency and that hook would break `composer update --no-dev`.) Model docblocks are committed, because they are what Larastan and your editor read.

## Fast tests

The suite is set up to run as fast as Pest allows:

- **Parallel:** `composer test` runs `pest --parallel`, one process per CPU core.
- **Test Impact Analysis (TIA):** `tests/Pest.php` enables `pest()->tia()->locally()`. On your machine, Pest re-runs only the tests affected by your changes and replays the rest from cache. The first run records the dependency graph and needs PCOV or Xdebug. TIA also needs a git repository with at least one commit, so it stays off (and the full suite runs) until you make your first commit. Use `--no-tia` for a full run, or `--tia --fresh` to re-record the graph. With `--ci`, TIA is off and the full suite runs.
- **Sharding:** CI splits the suite into two shards (`pest --ci --parallel --shard=N/2`), balanced by the timings in `tests/.pest/shards.json`. Refresh the timings with `./vendor/bin/pest --parallel --no-tia --update-shards` as the suite grows, and change the shard count in `.github/workflows/tests.yml`.

TIA can also download a dependency graph recorded by CI so a fresh clone replays immediately. That needs a `tia-baseline.yml` workflow and `pest()->tia()->baselined()`, which this kit does not enable: until the workflow has produced an artifact, `baselined()` fails the run. See [Sharing the baseline from CI](https://pestphp.com/docs/tia).

`brianium/paratest` is pinned to `~7.27.5` because ParaTest 7.28 breaks `pest --parallel` in Pest 5.3.5. Pest has fixed this on its 5.x branch ([pestphp/pest#2025](https://github.com/pestphp/pest/pull/2025)); remove the pin once a Pest release includes it.

## Email with Resend

`config/mail.php` defaults to the `resend` mailer and `config/services.php` reads `RESEND_API_KEY`. `.env.example` sets `MAIL_MAILER=log`, so local emails (verification, password reset) are written to `storage/logs/laravel.log` instead of being sent. In production:

```dotenv
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="hello@your-verified-domain.com"
```

## Monitoring with Nightwatch

`laravel/nightwatch` is installed and `config/nightwatch.php` is published. It stays disabled until you set:

```dotenv
NIGHTWATCH_ENABLED=true
NIGHTWATCH_TOKEN=your-environment-token
```

Nightwatch only sends data while its agent runs next to your app, so keep `php artisan nightwatch:agent` running under a process monitor (Laravel Cloud and Forge can do this for you). `LOG_STACK=single,nightwatch` in `.env.example` also sends logs to Nightwatch once it's enabled. Nightwatch is disabled in tests through `phpunit.xml`.

## Rector configuration

`rector.php` combines the setups of three people who publish their Laravel Rector configs:

- **Nuno Maduro** ([nunomaduro/laravel-starter-kit](https://github.com/nunomaduro/laravel-starter-kit)): the paths, the six prepared sets (dead code, code quality, coding style, type declarations, privatization, early return), `withPhpSets()`, the ten opt-in `LaravelSetList` quality sets, `withImportNames(removeUnusedImports: true)`, and skipping the rules that add `#[\Override]` to properties and align inherited method visibility. (Nuno also skips the methods variant of the `#[\Override]` rule; Rector 2.8 deprecated it and no longer registers it, so the kit leaves it out.)
- **Caleb White** ([calebdw](https://github.com/calebdw)): he doesn't publish an app `rector.php`, but he maintains much of rector-laravel. From that work the kit takes `withComposerBased(laravel: true)`, which applies the Laravel, Livewire and Faker upgrade sets up to the installed versions, instead of the deprecated `LaravelLevelSetList` and the removed `LaravelSetProvider`.
- **Josh Cirre** ([joshcirre/fission](https://github.com/joshcirre/fission), a Livewire and Flux starter kit): `PestSetList::CODING_STYLE` from Pest's first-party Rector plugin, which turns PHPUnit assertions into Pest expectations.

The kit adds two skips of its own: `SafeDeclareStrictTypesRector`, because Laravel's own files don't declare strict types, and `RenameClassRector` in `app/Models`, so Rector doesn't rewrite ide-helper's `@mixin \Eloquent` and regenerating docblocks stays stable.

## Differences from the official Livewire starter kit

- Tests are written in Pest's function syntax. The official kit's installer converts them with Pest Drift; that step had failed when this kit was generated, so it was redone by hand.
- Larastan runs at `level: max` instead of 7. Three type fixes in `app/Providers/FortifyServiceProvider.php` and `config/fortify.php` (typed request input and config reads), plus two `(string)` casts in the published `config/nightwatch.php`, make it pass.
- `composer test` only runs the tests; checks moved into `qa` and `ci:check`.
- CI runs Rector, Pint and Larastan in one job, and the tests in two shards.

## License

MIT
