# Laravel Starter Kit

A Laravel starter kit: an empty Laravel app on PostgreSQL with Livewire, Flux Pro and a welcome page, plus a strict quality toolchain, Resend for email, Fathom Analytics and Laravel Nightwatch. It has no authentication.

## What's included

| Area | Package | Notes |
| --- | --- | --- |
| Framework | Laravel 13, Livewire 4 | |
| UI | [Flux Pro](https://fluxui.dev), Tailwind CSS 4 | Flux's default theme; welcome page in `resources/views/welcome.blade.php` |
| Blade performance | [Blaze](https://github.com/livewire/blaze) (`livewire/blaze`) | Folds Blade components into their parent templates; Flux's components use it |
| Database | PostgreSQL | |
| Testing | [Pest 5](https://pestphp.com) | Parallel, time-balanced sharding, Test Impact Analysis |
| Formatting | [Laravel Pint](https://laravel.com/docs/pint) | `laravel` preset |
| Refactoring | [Rector](https://getrector.com) + [rector-laravel](https://github.com/driftingly/rector-laravel) + Pest's Rector plugin | See [Rector](#rector) |
| Static analysis | [Larastan](https://github.com/larastan/larastan) | `level: max` |
| IDE support | [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) | Model docs in `_ide_helper_models.php` |
| Email | [Resend](https://resend.com) (`resend/resend-php`) | Default mailer in `config/mail.php` |
| Analytics | [Fathom Analytics](https://usefathom.com) | Off until `FATHOM_SITE_ID` is set; see [Analytics with Fathom](#analytics-with-fathom) |
| Monitoring | [Laravel Nightwatch](https://nightwatch.laravel.com) | `config/nightwatch.php` published, disabled until `NIGHTWATCH_ENABLED=true` |
| Coding agents | [Laravel Boost](https://laravel.com/docs/boost) | MCP server, guidelines and skills for Amp, Claude Code and Codex; see [Coding agents](#coding-agents) |
| Agent output | `laravel/pao` | Compact output from Pest, Pint, Rector and Larastan for coding agents; set `PAO_DISABLE=1` for the normal output |

## Requirements

- PHP 8.4 or newer
- A [Flux Pro license](https://fluxui.dev/pricing), set up for Composer as in [Flux Pro](#flux-pro)
- Composer 2
- Node.js 22 and npm
- PostgreSQL, reachable with the defaults in `config/database.php` (`127.0.0.1:5432`, user `postgres`, no password)
- To create a project with `laravel new --using`: the Laravel installer (tested with 5.33) and `npx`
- [PCOV](https://github.com/krakjoe/pcov) or Xdebug for Pest's Test Impact Analysis locally (tests still run without one)

## Flux Pro

`livewire/flux-pro` is installed from `composer.fluxui.dev` (the `repositories` entry in `composer.json`). Composer logs in with your Flux account email as the username and your license key as the password, both on your [Flux dashboard](https://fluxui.dev/dashboard). Keep them out of the repository.

On your machine, store them once in Composer's global `auth.json` (`~/.config/composer/auth.json`). It's outside every project, so `laravel new` and `composer install` in any copy of the kit use it:

```bash
composer config --global http-basic.composer.fluxui.dev your-email your-license-key
```

To keep them in one project only, leave out `--global`. Composer then writes `auth.json` in the project root, which `.gitignore` excludes. Composer also reads the `COMPOSER_AUTH` environment variable:

```bash
export COMPOSER_AUTH='{"http-basic":{"composer.fluxui.dev":{"username":"your-email","password":"your-license-key"}}}'
```

In GitHub Actions, add two repository secrets under **Settings → Secrets and variables → Actions**: `FLUX_USERNAME` (your Flux account email) and `FLUX_LICENSE_KEY`. `.github/workflows/ci.yml` passes them to the autofix, Larastan and tests workflows, which set `COMPOSER_AUTH` from them. Add the same two secrets under **Settings → Secrets and variables → Dependabot**: CI runs for Dependabot's pull requests read those instead. Pull requests from forks don't receive the secrets, so CI can't install Flux Pro there.

## Getting started

Create a new project from this repository with the [Laravel installer](https://laravel.com/docs/installation#installing-php):

```bash
laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --no-boost --phpunit
```

The installer downloads the repository, installs the Composer dependencies from `composer.lock`, creates `.env`, generates the app key, creates and migrates the `laravel` database and the `laravel_testing` database that the tests use, and, in a terminal, installs and builds the frontend. From a coding agent or with `--no-node` it skips that step; run `npm install && npm run build` yourself.

- `--no-boost`: Laravel Boost is already installed and configured.
- `--phpunit`: the kit already uses Pest. Without this flag the installer sets Pest up again, which runs `composer update` and moves every dependency past `composer.lock`. The flag only skips that step; the project still uses Pest.
- Leave out `--database`.

Every project uses the databases `laravel` and `laravel_testing`. To give a project its own, set `DB_DATABASE` in `.env` and run `composer setup:database`; the tests use the same name with `_testing` appended.

Or clone it and run the setup script. It installs dependencies, creates `.env` from `.env.example`, generates the app key, creates and migrates both databases, and builds the frontend:

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
| `composer setup:database` | Creates and migrates the app database (`DB_DATABASE`) and the tests' database (`DB_DATABASE` with `_testing` appended). |
| `composer setup:test-database` | Creates and migrates the tests' database. |
| `composer dev` | Runs the app server, queue worker, log viewer and Vite together. |

`_ide_helper_models.php` holds the model docs and is committed; CI regenerates it (see [Continuous integration](#continuous-integration)). `_ide_helper.php` and `.phpstorm.meta.php` are git-ignored; run `composer ide-helper` after `composer update` to refresh them.

`composer update` also runs `php artisan boost:update`, which refreshes the agent guidelines and skills for the new package versions. Boost is a dev dependency, so `composer update --no-dev` fails at that step; deploy with `composer install --no-dev`.

## Fast tests

- **Database:** `phpunit.xml` sets `APP_ENV=testing`, so tests run against the app's database name with `_testing` appended (`config/database.php`), such as `laravel_testing`. In parallel runs each process gets its own `laravel_testing_test_N` copy.
- **No frontend build needed:** `tests/TestCase.php` calls `withoutVite()`, so the tests pass without `npm run build`.
- **Parallel:** `composer test` runs `pest --parallel`, one process per CPU core.
- **Test Impact Analysis (TIA):** `tests/Pest.php` enables `pest()->tia()->locally()`. On your machine, Pest re-runs only the tests affected by your changes and replays the rest from cache. The first run records the dependency graph and needs PCOV or Xdebug. TIA also needs a git repository with at least one commit, so it stays off (and the full suite runs) until you make your first commit. Use `--no-tia` for a full run, or `--tia --fresh` to re-record the graph. With `--ci`, TIA is off and the full suite runs.
- **Sharding:** CI splits the suite into two shards (`pest --ci --parallel --shard=N/2`), balanced by the timings in `tests/.pest/shards.json`. Refresh the timings with `./vendor/bin/pest --parallel --no-tia --update-shards` as the suite grows, and change the shard count in `.github/workflows/tests.yml`.

## Continuous integration

`.github/workflows/ci.yml` runs on pushes to `main` and on pull requests. It calls three workflows in order:

1. **Autofix** (`autofix.yml`) runs Rector, then IDE Helper against a migrated database, then Pint. If anything changed, it commits the result as `github-actions[bot]` and pushes it back to the branch.
2. **Larastan** (`larastan.yml`) at max level, check only.
3. **Tests** (`tests.yml`) in two time-balanced shards.

Autofix and the tests start a PostgreSQL 18 container with `.github/actions/setup-database`.

Larastan and the tests start after autofix succeeds and run on the autofix commit when there is one. The autofix commit doesn't start a new CI run; on a pull request it shows a `ci` run awaiting approval, which you can ignore. After an autofix commit, pull the branch before pushing again.

## Email with Resend

`config/mail.php` defaults to the `resend` mailer and `config/services.php` reads `RESEND_API_KEY`. `.env.example` sets `MAIL_MAILER=log`, so local emails are written to `storage/logs/laravel.log` instead of being sent. In production:

```dotenv
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="hello@your-verified-domain.com"
```

## Analytics with Fathom

`resources/views/partials/fathom.blade.php`, included in the page head, adds Fathom's tracking script when `FATHOM_SITE_ID` is set and `APP_DEBUG` is `false`. The script uses `data-spa="auto"`, so pages opened with `wire:navigate` are counted too. In production:

```dotenv
FATHOM_SITE_ID=ABCDEFGH
```

## Monitoring with Nightwatch

`laravel/nightwatch` is installed and `config/nightwatch.php` is published with Nightwatch disabled by default. To enable it, set:

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
- **Skills** in `.agents/skills/` (Amp, Codex) and `.claude/skills/` (Claude Code): `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `fluxui-development`, `tailwindcss-development`, `blaze-optimize`, `configure-nightwatch` and `infer-conventions`.

| File | Contents |
| --- | --- |
| `boost.json` | The configured agents, skills and integrations (Laravel Cloud, Nightwatch MCP and Sail are off) |
| `config/boost.php` | `enforce_tests`, which keeps Boost's test-writing guidelines in `AGENTS.md` and `CLAUDE.md` |
| `.mcp.json`, `.amp/settings.json`, `.codex/config.toml` | The MCP server for Claude Code, Amp and Codex |

To add or remove agents or integrations, run `php artisan boost:install`. To regenerate the guidelines and skills, run `php artisan boost:update` (`composer update` runs it too). Commit the files they change. Don't edit skills in `.agents/skills/` or `.claude/skills/` directly; `boost:update` overwrites them. To change one, put your version in `.ai/skills/<name>/` (a skill there replaces Boost's skill of the same name) and run `php artisan boost:update`, which links it into both folders. Commit `.ai/skills/` too.

The generated guidelines and skills come from Laravel Boost, Laravel Nightwatch and Livewire Blaze, all MIT-licensed.

## License

MIT
