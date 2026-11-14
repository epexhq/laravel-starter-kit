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
| Coding agents | [Laravel Boost](https://laravel.com/docs/boost) | MCP server, guidelines and skills for Amp, Claude Code and Codex; see [Coding agents](#coding-agents) |

## Requirements

- PHP 8.4 or newer
- Composer 2
- Node.js 22 and npm
- The database you choose: PostgreSQL (default) or MySQL, reachable with the defaults in `.env.example` (`127.0.0.1`, user `postgres` or `root`, no password), or SQLite, which needs no server
- To create a project with `laravel new --using`: the Laravel installer (tested with 5.33) and `npx`
- [PCOV](https://github.com/krakjoe/pcov) or Xdebug for Pest's Test Impact Analysis locally (tests still run without one)

## Getting started

Create a new project from this repository with the [Laravel installer](https://laravel.com/docs/installation#installing-php):

```bash
laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --no-boost
```

The installer downloads the repository, creates `.env`, generates the app key, and asks which database the app uses: PostgreSQL (the default), MySQL or SQLite. The choice is written into `config/database.php`, `.env.example`, `.env`, `phpunit.xml`, `composer.json` and the CI workflows, so the project uses that database from then on. The `laravel` database and the `testing` database for the tests are then created and migrated (with SQLite, `database/database.sqlite`; tests use an in-memory database). Finally the installer asks whether to install and build the frontend. Pass `--no-boost`: Laravel Boost is already installed and configured.

To choose the database without the prompt, for example with `--no-interaction`, set `DB_CONNECTION` to `pgsql`, `mysql` or `sqlite`:

```bash
DB_CONNECTION=mysql laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --no-boost --no-interaction
```

Without the prompt and without `DB_CONNECTION`, the project uses PostgreSQL. The installer's `--database` option doesn't choose the database; leave it out.

Or clone it and run the setup script. It installs dependencies, creates `.env` from `.env.example`, generates the app key, asks which database to use (or reads `DB_CONNECTION`) as above, creates and migrates the databases, and builds the frontend:

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
| `composer setup` | Installs dependencies, creates `.env`, generates the app key and IDE helper files, runs `setup:database`, and builds the frontend. |
| `composer setup:database` | Creates and migrates the app database and, with PostgreSQL or MySQL, the `testing` database. The first run in a fresh copy of the kit chooses the database first (see [Getting started](#getting-started)). |
| `composer setup:test-database` | PostgreSQL and MySQL only: creates and migrates the `testing` database that the tests use. |
| `composer dev` | Runs the app server, queue worker, log viewer and Vite together. |

`_ide_helper_models.php` holds the model docs and is committed; CI regenerates it (see [Continuous integration](#continuous-integration)). `_ide_helper.php` and `.phpstorm.meta.php` are git-ignored; run `composer ide-helper` after `composer update` to refresh them.

`composer update` also runs `php artisan boost:update`, which refreshes the agent guidelines and skills for the new package versions. Boost is a dev dependency, so `composer update --no-dev` fails at that step; deploy with `composer install --no-dev`.

## Fast tests

- **Database:** with PostgreSQL or MySQL, tests run against the `testing` database (set in `phpunit.xml`), and in parallel runs each process gets its own `testing_test_N` copy. With SQLite, each process uses an in-memory database.
- **Parallel:** `composer test` runs `pest --parallel`, one process per CPU core.
- **Test Impact Analysis (TIA):** `tests/Pest.php` enables `pest()->tia()->locally()`. On your machine, Pest re-runs only the tests affected by your changes and replays the rest from cache. The first run records the dependency graph and needs PCOV or Xdebug. TIA also needs a git repository with at least one commit, so it stays off (and the full suite runs) until you make your first commit. Use `--no-tia` for a full run, or `--tia --fresh` to re-record the graph. With `--ci`, TIA is off and the full suite runs.
- **Sharding:** CI splits the suite into two shards (`pest --ci --parallel --shard=N/2`), balanced by the timings in `tests/.pest/shards.json`. Refresh the timings with `./vendor/bin/pest --parallel --no-tia --update-shards` as the suite grows, and change the shard count in `.github/workflows/tests.yml`.

## Continuous integration

`.github/workflows/ci.yml` runs on pushes to `main` and on pull requests. It calls three workflows in order:

1. **Autofix** (`autofix.yml`) runs Rector, then IDE Helper against a migrated database, then Pint. If anything changed, it commits the result as `github-actions[bot]` and pushes it back to the branch. On pull requests from forks it fails and prints the diff instead.
2. **Larastan** (`larastan.yml`) at max level, check only.
3. **Tests** (`tests.yml`) in two time-balanced shards.

Autofix and the tests use the database chosen when the project was created: a PostgreSQL 18 or MySQL 8.4 service container, or SQLite.

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

## Coding agents

[Laravel Boost](https://laravel.com/docs/boost) is set up for Amp, Claude Code and Codex:

- **MCP server** (`php artisan boost:mcp`): tools to search the docs for the installed package versions (`search-docs`), read the database schema, run read-only queries, read the app and browser logs and the last error, and resolve app URLs.
- **Guidelines** for the installed versions of Laravel, Livewire, Pest and Pint, in `AGENTS.md` (Amp, Codex) and `CLAUDE.md` (Claude Code). Add your own instructions outside the `<laravel-boost-guidelines>` tags; Boost only rewrites what's inside them.
- **Skills** in `.agents/skills/` (Amp, Codex) and `.claude/skills/` (Claude Code): `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `fluxui-development`, `fortify-development`, `tailwindcss-development`, `blaze-optimize`, `configure-nightwatch` and `infer-conventions`.

| File | Contents |
| --- | --- |
| `boost.json` | The configured agents, skills and integrations (Laravel Cloud, Nightwatch MCP and Sail are off) |
| `config/boost.php` | `enforce_tests`, which keeps Boost's test-writing guidelines in `AGENTS.md` and `CLAUDE.md` |
| `.mcp.json`, `.amp/settings.json`, `.codex/config.toml` | The MCP server for Claude Code, Amp and Codex |

To add or remove agents or integrations, run `php artisan boost:install`. To regenerate the guidelines and skills, run `php artisan boost:update` (`composer update` runs it too). Commit the files they change. Don't edit skills in `.agents/skills/` or `.claude/skills/` directly; `boost:update` overwrites them. To change one, put your version in `.ai/skills/<name>/` (a skill there replaces Boost's skill of the same name) and run `php artisan boost:update`, which links it into both folders. Commit `.ai/skills/` too.

The generated guidelines and skills come from Laravel Boost, Laravel Fortify, Laravel Nightwatch and Livewire Blaze, all MIT-licensed.

## License

MIT
