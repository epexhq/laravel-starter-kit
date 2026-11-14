# Laravel Starter Kit

A Laravel starter kit based on the official [Laravel Livewire starter kit](https://github.com/laravel/livewire-starter-kit). It includes authentication with Fortify (two-factor and passkeys), profile and security settings, and Flux UI layouts, plus a strict quality toolchain, Resend for email, and Laravel Nightwatch.

## What's included

| Area | Package | Notes |
| --- | --- | --- |
| Framework | Laravel 13, Livewire 4 | |
| UI | [Flux](https://fluxui.dev) (free edition), Tailwind CSS 4 | |
| Auth | Laravel Fortify | Login, registration, email verification, password reset, 2FA, passkeys |
| Testing | [Pest 5](https://pestphp.com) | Parallel, time-balanced sharding, Test Impact Analysis |
| Formatting | [Laravel Pint](https://laravel.com/docs/pint) | `laravel` preset |
| Refactoring | [Rector](https://getrector.com) + [rector-laravel](https://github.com/driftingly/rector-laravel) + Pest's Rector plugin | See [Rector](#rector) |
| Static analysis | [Larastan](https://github.com/larastan/larastan) | `level: max` |
| IDE support | [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) | Model docs in `_ide_helper_models.php` |
| Email | [Resend](https://resend.com) (`resend/resend-php`) | Default mailer in `config/mail.php` |
| Monitoring | [Laravel Nightwatch](https://nightwatch.laravel.com) | `config/nightwatch.php` published, disabled until you enable it |
| Coding agents | `laravel-best-practices` skill | See [Agent skill](#agent-skill-laravel-best-practices) |

## Requirements

- PHP 8.4 or newer
- Composer 2
- Node.js 22 and npm
- PostgreSQL, reachable with the `DB_*` settings in `.env` (by default `postgres@127.0.0.1:5432`, no password)
- To create a project with `laravel new --using`: the Laravel installer (tested with 5.33) and `npx`
- [PCOV](https://github.com/krakjoe/pcov) or Xdebug for Pest's Test Impact Analysis locally (tests still run without one)

## Getting started

Create a new project from this repository with the [Laravel installer](https://laravel.com/docs/installation#installing-php):

```bash
laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --database=pgsql
```

The installer downloads the repository, creates `.env` with `DB_DATABASE=my_app`, generates the app key, creates the `testing` database for the test suite, asks to create the app database and runs the migrations, and asks whether to install and build the frontend. Pass `--database=pgsql`: without it, the installer switches the app to SQLite and skips the migrations. With `--no-interaction`, the installer doesn't create the app database; run `php artisan migrate` afterwards to create it.

Or clone it and run the setup script, which installs dependencies, creates `.env` from `.env.example`, generates the app key, creates and migrates the `laravel` and `testing` databases, and builds the frontend:

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
| `composer ide-helper` | Regenerates `_ide_helper.php`, `.phpstorm.meta.php` and `_ide_helper_models.php`. Needs a migrated database. |
| `composer setup` | Installs dependencies, creates `.env`, generates the app key and IDE helper files, creates and migrates the app and `testing` databases, and builds the frontend. |
| `composer setup:test-database` | Creates and migrates the `testing` database that the tests use. |
| `composer dev` | Runs the app server, queue worker, log viewer and Vite together. |

`_ide_helper_models.php` holds the model docs and is committed; CI regenerates it (see [Continuous integration](#continuous-integration)). `_ide_helper.php` and `.phpstorm.meta.php` are git-ignored; run `composer ide-helper` after `composer update` to refresh them.

## Fast tests

- **Database:** tests run against the PostgreSQL database `testing` (set in `phpunit.xml`). In parallel runs, each process gets its own `testing_test_N` copy.
- **Parallel:** `composer test` runs `pest --parallel`, one process per CPU core.
- **Test Impact Analysis (TIA):** `tests/Pest.php` enables `pest()->tia()->locally()`. On your machine, Pest re-runs only the tests affected by your changes and replays the rest from cache. The first run records the dependency graph and needs PCOV or Xdebug. TIA also needs a git repository with at least one commit, so it stays off (and the full suite runs) until you make your first commit. Use `--no-tia` for a full run, or `--tia --fresh` to re-record the graph. With `--ci`, TIA is off and the full suite runs.
- **Sharding:** CI splits the suite into two shards (`pest --ci --parallel --shard=N/2`), balanced by the timings in `tests/.pest/shards.json`. Refresh the timings with `./vendor/bin/pest --parallel --no-tia --update-shards` as the suite grows, and change the shard count in `.github/workflows/tests.yml`.

## Continuous integration

`.github/workflows/ci.yml` runs on pushes to `main` and on pull requests. It calls three workflows in order:

1. **Autofix** (`autofix.yml`) runs Rector, then IDE Helper against a migrated PostgreSQL database, then Pint. If anything changed, it commits the result as `github-actions[bot]` and pushes it back to the branch. On pull requests from forks it fails and prints the diff instead.
2. **Larastan** (`larastan.yml`) at max level, check only.
3. **Tests** (`tests.yml`) against PostgreSQL, in two time-balanced shards.

Larastan and the tests start after autofix finishes and run on the autofix commit when there is one. The autofix commit doesn't start a new CI run; on a pull request it shows a `ci` run awaiting approval, which you can ignore. After an autofix commit, pull the branch before pushing again.

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

## Rector

`rector.php` runs on `app`, `bootstrap/app.php`, `config`, `database`, `public`, `routes` and `tests`, and applies:

- the PHP version sets for the version in `composer.json`
- the dead code, code quality, coding style, type declaration, privatization and early return sets
- rector-laravel's upgrade sets for the installed Laravel, Livewire and Faker versions, and its Laravel code quality sets
- Pest's coding style set, which turns PHPUnit assertions into Pest expectations

## Agent skill: Laravel best practices

`.agents/skills/laravel-best-practices/` is the `laravel-best-practices` skill from Nuno Maduro's starter kit. Coding agents that read project skills from `.agents/skills/`, such as Amp and Codex, load it when they write or review Laravel code. Claude Code reads `.claude/skills/` instead; copy or symlink the folder there if you use it.

- **Upstream:** [nunomaduro/laravel-starter-kit](https://github.com/nunomaduro/laravel-starter-kit), path `.agents/skills/laravel-best-practices/`
- **Copied from commit:** [`29d361dae2d646d9c3eaf053d819c4a88a29ee26`](https://github.com/nunomaduro/laravel-starter-kit/commit/29d361dae2d646d9c3eaf053d819c4a88a29ee26) (2026-11-10); the skill last changed upstream in [`bb17287be1534112111d8acc7be631a47200601d`](https://github.com/nunomaduro/laravel-starter-kit/commit/bb17287be1534112111d8acc7be631a47200601d)
- **Origin and license:** the skill is generated by [Laravel Boost](https://github.com/laravel/boost) (`author: laravel`, `license: MIT` in `SKILL.md`). Boost's MIT license (Copyright (c) Taylor Otwell) is kept next to it in `LICENSE.md`. Nuno's starter kit is also MIT-licensed.

Local changes to the upstream files (re-apply them when syncing a newer upstream commit):

- `SKILL.md` and `rules/style.md`: the three references to `search-docs`, a Laravel Boost MCP tool this kit doesn't install, point to the [Laravel 13 docs](https://laravel.com/docs/13.x) instead.
- `rules/style.md`: the two `'App\Models\User'` examples contained NUL bytes where `\U` had been mangled upstream (which also made Git treat the file as binary), and the `strrchr()` example's `'\'` is now `'\\'`.
- `LICENSE.md` added.

## License

MIT
