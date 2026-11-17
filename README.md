# Laravel Starter Kit

A Laravel starter kit: an empty Laravel app on PostgreSQL with Livewire, Flux, a layout and a welcome page, plus a strict quality toolchain, Laravel Scout, Resend for email, Fathom Analytics, Laravel Nightwatch, Laravel Cloud support and Laravel Boost for coding agents. It has no authentication and no `User` model.

## What's included

| Area | Package | Notes |
| --- | --- | --- |
| Framework | Laravel 13, Livewire 4 | |
| UI | [Flux](https://fluxui.dev) (free), Tailwind CSS 4 | Flux's default theme; layout in `resources/views/layouts/app.blade.php`, welcome page in `resources/views/welcome.blade.php`; see [Livewire](#livewire) and [Upgrading a project to Flux Pro](#upgrading-a-project-to-flux-pro) |
| Blade performance | [Blaze](https://github.com/livewire/blaze) (`livewire/blaze`) | Folds Blade components into their parent templates; Flux's components use it |
| Database | PostgreSQL | |
| Testing | [Pest 5](https://pestphp.com) | Parallel, time-balanced CI shards, an architecture test, and Test Impact Analysis on demand (`composer test:tia`) |
| Formatting | [Laravel Pint](https://laravel.com/docs/pint) | `laravel` preset |
| Refactoring | [Rector](https://getrector.com) + [rector-laravel](https://github.com/driftingly/rector-laravel) + Pest's Rector plugin | See [Rector](#rector) |
| Static analysis | [Larastan](https://github.com/larastan/larastan) + [larastan-livewire](https://github.com/calebdw/larastan-livewire) + [Pest's PHPStan plugin](https://pestphp.com/docs/phpstan) | `level: max` on the app and `tests/`; see [Static analysis](#static-analysis) |
| IDE support | [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) | Model docs in `_ide_helper_models.php` |
| Email | [Resend](https://resend.com) (`resend/resend-php`) | `MAIL_MAILER=resend` in production; see [Email with Resend](#email-with-resend) |
| Analytics | [Fathom Analytics](https://usefathom.com) | Off until `FATHOM_SITE_ID` is set; see [Analytics with Fathom](#analytics-with-fathom) |
| Search | [Laravel Scout](https://laravel.com/docs/scout) | Scout's `collection` driver until `SCOUT_DRIVER` is set; Typesense is an optional install; see [Search with Scout](#search-with-scout) |
| Monitoring | [Laravel Nightwatch](https://nightwatch.laravel.com) | `config/nightwatch.php` published; `.env.example` turns it off locally |
| Hosting | [Laravel Cloud](https://cloud.laravel.com) | `league/flysystem-aws-s3-v3` for Object Storage, `aws/aws-sdk-php` for managed queues; see [Deploy to Laravel Cloud](#deploy-to-laravel-cloud) |
| Coding agents | [Laravel Boost](https://laravel.com/docs/boost) | MCP server, guidelines and skills for Amp, Claude Code and Codex; see [Coding agents](#coding-agents) |
| Agent output | `laravel/pao` | Compact output from Pest, Rector and Larastan for coding agents; set `PAO_DISABLE=1` for the normal output. Pint prints JSON on its own when it detects a coding agent |

## Requirements

- PHP 8.4 or newer
- Composer 2
- Node.js 26, the LTS version in `.nvmrc` (`nvm use` reads it), and npm
- PostgreSQL, reachable with the kit's defaults (`127.0.0.1:5432`, user `postgres`, no password)
- Valkey or Redis at `127.0.0.1:6379` and PHP's `redis` extension, for the cache, the queue and sessions (`CACHE_STORE`, `QUEUE_CONNECTION` and `SESSION_DRIVER` default to `redis`). Without one, set all three to `database` in `.env`
- To create a project with `laravel new --using`: the Laravel installer (tested with 5.33) and `npx`
- [PCOV](https://github.com/krakjoe/pcov) or Xdebug, only for `composer test:tia`

## Getting started

Create a new project from this repository with the [Laravel installer](https://laravel.com/docs/installation#installing-php):

```bash
laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --no-boost --phpunit
```

The installer downloads the repository, installs the Composer dependencies from `composer.lock`, creates `.env`, generates the app key, creates and migrates the `laravel` database and the `laravel_testing` database that the tests use, and, in a terminal, installs and builds the frontend. With `--no-node`, or when it detects a coding agent, it skips that step; run `npm install && npm run build` yourself.

The installer detects a coding agent from environment variables such as `AI_AGENT`, `AMP_CURRENT_THREAD_ID`, `CLAUDECODE`, `CLAUDE_CODE`, `CODEX_SANDBOX` and `CODEX_THREAD_ID` (the full list is in `laravel/agent-detector`'s `AgentDetector.php`). To run it from an agent's shell as if from a terminal, unset them:

```bash
env -u AI_AGENT -u AMP_CURRENT_THREAD_ID -u CLAUDECODE -u CLAUDE_CODE -u CODEX_SANDBOX -u CODEX_THREAD_ID \
  laravel new my-app --using=https://github.com/epexhq/laravel-starter-kit --no-boost --phpunit
```

- `--no-boost`: Laravel Boost is already installed and configured.
- `--phpunit`: the kit already uses Pest. Without this flag the installer sets Pest up again, which runs `composer update` and moves every dependency past `composer.lock`. The flag only skips that step; the project still uses Pest.
- Leave out `--database`: the kit is already set up for PostgreSQL, and the flag makes the installer rewrite `.env.example`.

PostgreSQL must be running: if the installer can't connect, it stops with the connection error. Start PostgreSQL, then run `composer setup:database` in the project.

Every project uses the databases `laravel` and `laravel_testing`. To give a project its own, set `DB_DATABASE` in `.env` and run `composer setup:database`; the tests use the same name with `_testing` appended.

After installing, make the project your own:

- In `composer.json`, set `name` (such as `acme/my-app`) and `description`.
- In `.env` and `.env.example`, set `APP_NAME`.
- Replace this `README.md`.
- A new project keeps the kit's MIT `LICENSE` file and `"license": "MIT"` in `composer.json`. For a private project, delete `LICENSE` and set `"license": "proprietary"`.

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
| `composer test` | Runs the full suite with `pest --parallel`. |
| `composer test:tia` | Runs `pest --parallel --tia`, which re-runs only the tests affected by your changes. See [Fast tests](#fast-tests). |
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

- **Database and services:** `phpunit.xml` sets `APP_ENV=testing`, clears `DB_URL` and sets the cache, mail, queue, session, filesystem, broadcasting, search and Nightwatch settings in `$_SERVER`, so they win over your shell and `.env`. It also points `APP_CONFIG_CACHE`, `APP_EVENTS_CACHE` and `APP_ROUTES_CACHE` at files that don't exist, so tests ignore the config, events and routes cached by `php artisan optimize`. Tests therefore run against the app's database name with `_testing` appended (`config/database.php`), such as `laravel_testing`, however you run them: `composer test`, `php artisan test` or `vendor/bin/pest`. In parallel runs each process gets its own `laravel_testing_test_N` copy. `composer setup:test-database` migrates the same database, also ignoring a cached config.
- **No frontend build needed:** `tests/TestCase.php` calls `withoutVite()`, so the tests pass without `npm run build`.
- **Parallel:** `composer test` runs `pest --parallel`, one process per CPU core.
- **Test location:** `phpunit.xml` runs every `*Test.php` file under `tests/`, including unit tests made with `php artisan make:test --unit`.
- **Test Impact Analysis (TIA):** `composer test:tia` runs Pest's experimental Test Impact Analysis: it re-runs only the tests affected by your changes and replays the rest from cache. The first run records the dependency graph and needs PCOV or Xdebug, and TIA needs a git repository with at least one commit. `tests/Pest.php` names `main` as the default branch that TIA compares against; change it if yours differs. Run `./vendor/bin/pest --parallel --tia --fresh` to re-record the graph. `composer test`, `composer qa` and CI don't use TIA and always run the full suite.
- **Sharding:** CI splits the suite into two shards (`pest --parallel --shard=N/2`), balanced by the timings in `tests/.pest/shards.json`. Refresh the timings with `./vendor/bin/pest --parallel --update-shards` as the suite grows, and change the shard count in `.github/workflows/tests.yml`.
- **Architecture test:** `tests/ArchTest.php` fails when a Livewire component isn't a class in `app/Livewire`; see [Livewire](#livewire).

## Continuous integration

`.github/workflows/ci.yml` runs on pushes to `main` and on pull requests:

1. **Autofix** (`autofix.yml`) runs Rector, then IDE Helper against a migrated database, then Pint.
   - On pull requests from branches of this repository, it commits any change as `github-actions[bot]` and pushes it to the branch.
   - On pushes to `main`, on pull requests from forks and on Dependabot's pull requests, it runs in check mode (`rector --dry-run`, `pint --test`, and a comparison of the regenerated `_ide_helper_models.php`) and fails when anything would change. It never commits.
2. Then, in parallel:
   - **Larastan** (`larastan.yml`) at max level, check only.
   - **Tests** (`tests.yml`) in two time-balanced shards.
   - **Frontend build** (`build.yml`): `npm ci` and `npm run build` on the Node version in `.nvmrc`. It installs the Composer dependencies too, because `resources/css/app.css` imports Flux's CSS from `vendor/`.

All of them install PHP 8.4 with `.github/actions/setup-php`; change the version there. Autofix and the tests start a PostgreSQL 18 container with `.github/actions/setup-database`. CI needs no secrets.

Larastan, the tests and the build run on the autofix commit when there is one. The autofix commit doesn't start a new CI run; on a pull request it shows a `ci` run awaiting approval, which you can ignore. After an autofix commit, pull the branch before pushing again.

## Livewire

Livewire components are classes in `app/Livewire` with their views in `resources/views/livewire`, so Rector, Pint and Larastan check them. `config/livewire.php` makes `php artisan make:livewire` create this kind (`make_command.type`), and `tests/ArchTest.php` fails on single-file and multi-file components.

A full-page component uses `resources/views/layouts/app.blade.php` (`layouts::app`), which includes the page head: fonts, the Vite assets, `@fluxAppearance`, Fathom and `@fluxScripts`. To add a page:

```bash
php artisan make:livewire Dashboard
```

```php
// routes/web.php
Route::livewire('/dashboard', App\Livewire\Dashboard::class);
```

Then run `composer qa`: Rector adds the `render()` return type that Larastan requires. To set the page title, add `#[Title('Dashboard')]` (`Livewire\Attributes\Title`) to the class. Blade views use the same layout with `<x-layouts::app :title="__('Welcome')">`, as `welcome.blade.php` does.

## Models

The kit has no models. `app/Models` and `database/factories` are empty, so `php artisan make:model Post --migration --factory` creates `App\Models\Post`, its migration and `Database\Factories\PostFactory`. After a migration, run `composer ide-helper` and commit `_ide_helper_models.php`.

Dates are immutable: `AppServiceProvider` calls `Date::use(CarbonImmutable::class)`, so `now()`, the `Date` facade and model date casts return `CarbonImmutable`, and `->addDay()` returns a new instance instead of changing the original.

## Upgrading a project to Flux Pro

The kit uses free Flux. To use Flux Pro in a project, run:

```bash
php artisan flux:activate
```

It asks for your Flux account email and license key and saves them to the project's `auth.json`, which `.gitignore` excludes, then adds the `composer.fluxui.dev` repository to `composer.json` and requires `livewire/flux-pro`. Commit `composer.json` and `composer.lock`.

Every other place that runs `composer install` then needs the credentials:

- **GitHub Actions:** add the repository secrets `FLUX_USERNAME` and `FLUX_LICENSE_KEY` (and the same two as Dependabot secrets), pass them from `ci.yml` with `secrets: inherit`, and set this on each workflow job that installs Composer dependencies:

  ```yaml
  env:
    COMPOSER_AUTH: '{"http-basic":{"composer.fluxui.dev":{"username":"${{ secrets.FLUX_USERNAME }}","password":"${{ secrets.FLUX_LICENSE_KEY }}"}}}'
  ```

  Pull requests from forks don't receive secrets, so CI can't install Flux Pro there.
- **Laravel Cloud:** add `FLUX_USERNAME` and `FLUX_LICENSE_KEY` as variables and start the build commands with `composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY"`.
- **Amp orbs:** add `COMPOSER_AUTH` with the same JSON as a project secret.

## Email with Resend

`config/services.php` reads `RESEND_API_KEY` for the `resend` mailer in `config/mail.php`. `MAIL_MAILER` defaults to `smtp` on `127.0.0.1:1025`, Mailpit's SMTP port, so local emails land in Mailpit; set `MAIL_MAILER=log` to write them to `storage/logs/laravel.log` instead. In production:

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

## Search with Scout

`laravel/scout` is installed without a search server. Until `SCOUT_DRIVER` is set, Scout uses its `collection` driver, which loads the models from the database and filters them in PHP, so it suits small tables. Tests always use it (`phpunit.xml`). For larger tables, set `SCOUT_DRIVER=database`: Scout's [database engine](https://laravel.com/docs/scout#database-engine) searches the model's table with `ilike` queries, or PostgreSQL full-text search on the columns marked `#[SearchUsingFullText]`, and needs no index.

Make a model searchable with Scout's `Searchable` trait and a `toSearchableArray()` method that returns the columns to search, then search with `Post::search('laravel')->get()`. The database engine searches only the keys that method returns; without it, the model has none and every row matches.

```php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
```

### Adding Typesense

The kit doesn't install [Typesense](https://typesense.org). To use it in a project, run:

```bash
composer config allow-plugins.php-http/discovery false
composer require typesense/typesense-php
```

The first command answers Composer's question about the `php-http/discovery` plugin that the Typesense client requires; at runtime the client finds Guzzle, which Laravel already installs. Commit `composer.json` and `composer.lock`. Then set:

```dotenv
SCOUT_DRIVER=typesense
TYPESENSE_HOST=127.0.0.1
TYPESENSE_API_KEY=your-api-key
```

`TYPESENSE_PORT` (default `8108`) and `TYPESENSE_PROTOCOL` (default `http`) reach other servers, such as [Typesense Cloud](https://cloud.typesense.org) on `443` and `https`. On Laravel Cloud, add these as custom variables. CI needs nothing: the tests use the `collection` driver.

Typesense also needs a collection schema with the fields from `toSearchableArray()`, and the fields to search, both defined on the model (Typesense doesn't support `php artisan scout:sync-index-settings`):

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    /** @return array{id: string, title: string, created_at: int} */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'created_at' => $this->created_at?->getTimestamp() ?? 0,
        ];
    }

    /** @return array<string, mixed> */
    public function typesenseCollectionSchema(): array
    {
        return [
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'created_at', 'type' => 'int64'],
            ],
            'default_sorting_field' => 'created_at',
        ];
    }

    /** @return array<string, mixed> */
    public function typesenseSearchParameters(): array
    {
        return ['query_by' => 'title'];
    }
}
```

Scout indexes a model when it's saved; to index existing records, run `php artisan scout:import "App\Models\Post"`.

## Monitoring with Nightwatch

`laravel/nightwatch` is installed and `config/nightwatch.php` is published as Nightwatch ships it, plus `X-CSRF-TOKEN` and `current_password` in its redaction lists. Nightwatch is enabled unless `NIGHTWATCH_ENABLED=false`. `.env.example` sets `NIGHTWATCH_ENABLED=false`, so it's off locally. To use it, remove that line from `.env` and set:

```dotenv
NIGHTWATCH_TOKEN=your-environment-token
```

Nightwatch only sends data while its agent runs next to your app, so keep `php artisan nightwatch:agent` running under a process monitor (Laravel Cloud and Forge can do this for you). Laravel Cloud sets `LOG_STACK` itself when Nightwatch is connected; on other hosts, set `LOG_STACK=single,nightwatch` to send logs to Nightwatch too. Nightwatch is disabled in tests through `phpunit.xml`.

By default Nightwatch records every request, command, job, query and exception, logs from `debug` up, and the source code around exceptions. It records request URLs with their query strings and request headers, except those in `redact_headers` (`Authorization`, `Cookie`, `Proxy-Authorization`, `X-CSRF-TOKEN`, `X-XSRF-TOKEN`). It doesn't record request bodies unless `NIGHTWATCH_CAPTURE_REQUEST_PAYLOAD=true`, and then redacts the fields in `redact_payload_fields`. Review this before monitoring an app with personal data; the `NIGHTWATCH_*` variables in `config/nightwatch.php` change the sampling rates, log level, filters and redaction lists. Setting `NIGHTWATCH_REDACT_HEADERS` or `NIGHTWATCH_REDACT_PAYLOAD_FIELDS` replaces a list, so include the defaults.

Query exception messages, which reach logs and Nightwatch, show `?` in place of the bound values: `DB_MASK_BINDINGS` defaults to `true` in `config/database.php`. PostgreSQL's own error text can still quote a value, such as `invalid input syntax for type integer: "…"` or a unique violation's `Key (email)=(…)`.

## Deploy to Laravel Cloud

Create an application in [Laravel Cloud](https://cloud.laravel.com) from your project's repository, then set up its environment.

**Settings:** PHP 8.4 and Node 26.

**Build commands:**

```bash
composer install --no-dev
npm ci --audit false
npm run build
php artisan optimize
```

**Deploy command:**

```bash
php artisan migrate --force
```

In production, `AppServiceProvider` refuses `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback` and `db:wipe` (`DB::prohibitDestructiveCommands()`), so undo a migration with a new one.

**Resources** on the environment's canvas:

- **Database:** a Laravel Serverless Postgres database. Cloud sets the `DB_*` variables.
- **Email:** attach Resend. Cloud sets `RESEND_API_KEY`, `MAIL_MAILER`, `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`.
- **Nightwatch:** click **Connect Nightwatch**, turn on monitoring and enter your token. Cloud sets `NIGHTWATCH_TOKEN` and runs the agent.
- **Files:** attach a Laravel Object Storage bucket as the default disk for private files (`Storage::put()`). For public files, attach a public bucket with the disk name `public` (`Storage::disk('public')`). Don't pass a `visibility` option.
- **Cache:** attach a Laravel Valkey cache. Cloud sets the `REDIS_*` variables and `CACHE_STORE`. The cache, sessions and queue default to `redis`, so they all use it. Pick its eviction policy for that: Cloud recommends `allkeys-lru`, which silently deletes queued jobs and sessions when memory fills. `volatile-lru` deletes only keys with an expiry, so it keeps queued jobs but can still end sessions; `noeviction` deletes nothing, and writes fail when memory is full.
- **Queues:** to run jobs, add a managed queue. Cloud sets `QUEUE_CONNECTION=cloud`, runs and scales the workers, and jobs then run on Cloud's queue instead of Valkey. To keep jobs on Valkey, add a queue worker background process to the App cluster or to a worker cluster instead; without either, nothing runs queued jobs.
- **Search:** Scout needs no resource. Set `SCOUT_DRIVER=database` as a custom variable to search the database instead of the `collection` driver, or see [Adding Typesense](#adding-typesense).
- **WebSockets:** see [Real-time with Reverb](#real-time-with-reverb).
- **Scheduler:** turn on **Scheduler** on the App cluster. Use `->onOneServer()` on tasks that must run once when the cluster has more than one replica.

Cloud's startup and readiness health checks use the `/up` route from `bootstrap/app.php`. `AppServiceProvider` makes it fail when the database, the cache store or the session store is unreachable.

**Environment variables** to add yourself, as custom variables or linked secrets:

```dotenv
APP_NAME="My App"
```

Without a Valkey cache, set `CACHE_STORE=database`, `SESSION_DRIVER=database` and, without a managed queue, `QUEUE_CONNECTION=database`. Optional:

```dotenv
# Fathom Analytics
FATHOM_SITE_ID=ABCDEFGH
# Without Nightwatch connected
NIGHTWATCH_ENABLED=false
```

If the environment has no `APP_KEY`, add the output of `php artisan key:generate --show` too.

Add only these variables, those three if you need them, `SCOUT_DRIVER` and the Typesense variables if you use them, and `BROADCAST_CONNECTION` from [Real-time with Reverb](#real-time-with-reverb). Don't copy your local `.env` into Cloud or push it with `cloud deploy --push-env`: a variable you set replaces the one Cloud sets, so local values such as `DB_USERNAME`, `MAIL_MAILER` and `LOG_STACK` would replace Cloud's database, mail and log settings.

**Cloud CLI.** To deploy and manage Cloud from the terminal, and for the `deploying-to-cloud` agent skill, install the [Cloud CLI](https://cloud.laravel.com/docs/api/cli) in the project and run it from `vendor/bin`:

```bash
composer require --dev laravel/cloud-cli
./vendor/bin/cloud auth
./vendor/bin/cloud deploy
```

`cloud auth` opens a browser. Where there is none, such as in an Amp orb, create an API token in Cloud and either set it as `LARAVEL_CLOUD_TOKEN` or save it with `./vendor/bin/cloud auth:token --add`. The skill uses the project's CLI and falls back to a global install (`composer global require laravel/cloud-cli`, run as `cloud`) only when the project has none.

## Real-time with Reverb

When a project needs WebSockets, run these in a terminal and accept the defaults:

```bash
php artisan install:broadcasting --reverb
BROADCAST_CONNECTION=null php artisan reverb:install
npm run build
```

The first command prints a `Failed to create broadcaster for connection "reverb"` error; the second command writes the missing Reverb settings to `.env`.

Together they install `laravel/reverb`, `laravel-echo` and `pusher-js`, add `config/broadcasting.php`, `config/reverb.php`, `routes/channels.php` and `resources/js/echo.js`, and set `BROADCAST_CONNECTION=reverb` and the `REVERB_*` and `VITE_REVERB_*` variables in `.env`. `composer dev` then also runs `php artisan reverb:start`. Then delete the example `App.Models.User.{id}` channel from `routes/channels.php`: the kit has no `User` model, and Larastan rejects the channel. Commit the files they add and change.

On Laravel Cloud:

1. Attach WebSockets to the environment (**Add resource → WebSockets**). Cloud sets the `REVERB_*` and `VITE_REVERB_*` variables.
2. Then add `BROADCAST_CONNECTION=reverb` to the environment's variables and deploy. Add it only after WebSockets is attached.

## Rector

`rector.php` runs on `app`, `bootstrap/app.php`, `config`, `database`, `public`, `routes` and `tests`, and applies:

- the PHP version sets for the version in `composer.json`
- the dead code, code quality, coding style, type declaration, privatization and early return sets
- rector-laravel's upgrade sets for the installed Laravel, Livewire and Faker versions, and its Laravel code quality sets
- Pest's coding style set, which turns PHPUnit assertions into Pest expectations

## Static analysis

`phpstan.neon` runs Larastan at `level: max` on `app`, `bootstrap/app.php`, `config`, `database`, `routes` and `tests`, with:

- larastan-livewire, which types Livewire's computed properties and `Livewire::test()`
- Pest's PHPStan plugin, which types `$this` in test closures as the test case bound in `tests/Pest.php`, and the value in `expect()` chains. It also reports expectations that can't fail, such as the `expect(true)->toBeTrue()` placeholder in a test made with `php artisan make:test --pest --unit`
- `tests/PHPStan/LivewireTesting.stub`, which types the `Livewire::test()` chain after `assertHasErrors()`, `assertDispatched()`, `assertRedirect()` and Livewire's other test assertions
- `parseModelCastsMethod`, so a `datetime` cast in a model's `casts()` method is a Carbon instance

## Coding agents

[Laravel Boost](https://laravel.com/docs/boost) is set up for Amp, Claude Code and Codex:

- **MCP server** (`php artisan boost:mcp`): tools to search the docs for the installed package versions (`search-docs`), read the database schema, run read-only queries, read the app and browser logs and the last error, and resolve app URLs.
- **Guidelines** in `AGENTS.md` (Amp, Codex) and `CLAUDE.md` (Claude Code): first the project's conventions from `.ai/guidelines/starter-kit.md` (run `composer qa` before every commit, PostgreSQL only, no `User` model, class-based Livewire components, `composer test:tia`, what the cache can hold, which Scout driver to use), then Boost's guidelines for the installed versions of Laravel, Livewire, Pest and Pint. To change the project's conventions, edit or add files in `.ai/guidelines/` and run `php artisan boost:update`; Boost rewrites everything inside the `<laravel-boost-guidelines>` tags.
- **Skills** in `.agents/skills/` (Amp, Codex) and `.claude/skills/` (Claude Code): `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `fluxui-development`, `tailwindcss-development`, `blaze-optimize`, `configure-nightwatch`, `scout-development`, `deploying-to-cloud` and `infer-conventions`.

| File | Contents |
| --- | --- |
| `boost.json` | The configured agents, skills and integrations (Laravel Cloud is on; Nightwatch MCP and Sail are off) |
| `.mcp.json`, `.amp/settings.json`, `.codex/config.toml` | The MCP server for Claude Code, Amp and Codex |

To add or remove agents or integrations, run `php artisan boost:install`. To regenerate the guidelines and skills, run `php artisan boost:update` (`composer update` runs it too). Commit the files they change. Don't edit skills in `.agents/skills/` or `.claude/skills/` directly; `boost:update` overwrites them. To change one, put your version in `.ai/skills/<name>/` (a skill there replaces Boost's skill of the same name) and run `php artisan boost:update`, which links it into both folders. Commit `.ai/skills/` too.

The generated guidelines and skills come from Laravel Boost, Laravel Nightwatch and Livewire Blaze, all MIT-licensed.

### Amp orbs

`.agents/setup` prepares an [Amp](https://ampcode.com) orb and `.amp/services.yaml` runs its services. Start them with `amp orb services ensure`, which prints the portal URLs.

| Service | The orb's `.env` | Open it |
| --- | --- | --- |
| App (`php artisan serve`) | | app portal |
| PostgreSQL 18 | the kit's defaults | |
| Valkey | the kit's defaults (cache, queue and sessions) | |
| Mailpit | the kit's defaults (`smtp`, port 1025) | inbox portal |
| RustFS | `FILESYSTEM_DISK=s3`, bucket `app` | console portal, `orb` / `orb-secret` |

Setup installs the dependencies, builds the frontend and creates both databases; it needs no secrets. It writes the `# Orb services` block to `.env` once, so your later edits to it stay. The app portal serves the built frontend, so run `npm run build` after changing CSS or JavaScript. The block also sets `TRUSTED_PROXIES=127.0.0.1`, which `config/trustedproxy.php` reads, so pages opened through a portal link to its HTTPS URLs. Leave `TRUSTED_PROXIES` unset on Laravel Cloud. Jobs run when `php artisan queue:work` or `composer dev` is running. File URLs point at `127.0.0.1:9000` and open only inside the orb; browse stored files in the RustFS console.

## License

MIT. See `LICENSE`.
