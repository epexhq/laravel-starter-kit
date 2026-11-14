<?php

/*
 * Chooses the app's database once, when the project is created. `laravel new` runs it through
 * `composer setup:database`, and so does `composer setup`. It writes the choice into
 * config/database.php, .env.example, .env, phpunit.xml, the CI workflows and composer.json,
 * deletes itself, and then creates and migrates the databases.
 *
 * Set DB_CONNECTION to pgsql, mysql or sqlite to choose without a prompt. Without a prompt
 * and without DB_CONNECTION, PostgreSQL is kept.
 */

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

require __DIR__.'/vendor/autoload.php';

$databases = ['pgsql' => 'PostgreSQL', 'mysql' => 'MySQL', 'sqlite' => 'SQLite'];

$database = getenv('DB_CONNECTION') ?: select('Which database will your application use?', $databases, default: 'pgsql');

if (! is_string($database) || ! isset($databases[$database])) {
    fwrite(STDERR, 'DB_CONNECTION must be one of: '.implode(', ', array_keys($databases)).PHP_EOL);

    exit(1);
}

$serverEnvironment = static fn (string $name, string $port, string $username): string => <<<ENV
    # {$name} is the default connection (config/database.php). The values below are its
    # defaults; uncomment and change the ones your server needs. `composer setup` creates the
    # database if it doesn't exist, and tests use a separate "testing" database (phpunit.xml).
    # DB_HOST=127.0.0.1
    # DB_PORT={$port}
    # DB_DATABASE=laravel
    # DB_USERNAME={$username}
    # DB_PASSWORD=

    ENV;

$environment = [
    'pgsql' => $serverEnvironment('PostgreSQL', '5432', 'postgres'),
    'mysql' => $serverEnvironment('MySQL', '3306', 'root'),
    'sqlite' => <<<'ENV'
        # SQLite is the default connection (config/database.php), stored in database/database.sqlite.
        # Tests use an in-memory database (phpunit.xml).

        ENV,
];

$services = [
    'pgsql' => <<<'YAML'
            services:
              postgres:
                image: postgres:18
                env:
                  POSTGRES_HOST_AUTH_METHOD: trust
                ports:
                  - 5432:5432
                options: >-
                  --health-cmd pg_isready
                  --health-interval 5s
                  --health-timeout 5s
                  --health-retries 10


        YAML,
    'mysql' => <<<'YAML'
            services:
              mysql:
                image: mysql:8.4
                env:
                  MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
                ports:
                  - 3306:3306
                options: >-
                  --health-cmd "mysqladmin ping -h 127.0.0.1"
                  --health-interval 5s
                  --health-timeout 5s
                  --health-retries 10


        YAML,
    'sqlite' => '',
];

$setupScripts = <<<'JSON'
            "setup:database": [
                "@php artisan migrate --force --graceful --ansi",
                "@composer run-script setup:test-database"
            ],
            "setup:test-database": [
                "@putenv DB_DATABASE=testing",
                "@php artisan migrate --force --graceful --ansi"
            ],

    JSON;

if ($database === 'sqlite') {
    $setupScripts = <<<'JSON'
                "setup:database": [
                    "@php artisan migrate --force --graceful --ansi"
                ],

        JSON;
}

$changes = [
    'config/database.php' => [
        "'default' => env('DB_CONNECTION', 'pgsql')," => "'default' => env('DB_CONNECTION', '{$database}'),",
    ],
    '.env.example' => [$environment['pgsql'] => $environment[$database]],
    '.env' => [$environment['pgsql'] => $environment[$database]],
    'phpunit.xml' => [
        '<env name="DB_CONNECTION" value="pgsql"/>' => "<env name=\"DB_CONNECTION\" value=\"{$database}\"/>",
        '<env name="DB_DATABASE" value="testing"/>' => $database === 'sqlite'
            ? '<env name="DB_DATABASE" value=":memory:"/>'
            : '<env name="DB_DATABASE" value="testing"/>',
    ],
    '.github/workflows/autofix.yml' => [
        $services['pgsql'] => $services[$database],
        'extensions: pdo_pgsql' => "extensions: pdo_{$database}",
    ],
    '.github/workflows/tests.yml' => [
        $services['pgsql'] => $services[$database],
        'extensions: pdo_pgsql' => "extensions: pdo_{$database}",
    ],
    'composer.json' => [
        <<<'JSON'
                "setup:database": [
                    "@php configure-database.php"
                ],
                "setup:test-database": [
                    "@putenv DB_DATABASE=testing",
                    "@php artisan migrate --force --graceful --ansi"
                ],

        JSON => $setupScripts,
    ],
];

foreach ($changes as $file => $replacements) {
    $path = __DIR__.'/'.$file;

    if ($file === '.env' && ! file_exists($path)) {
        continue;
    }

    $contents = (string) file_get_contents($path);

    foreach ($replacements as $search => $replace) {
        // .env is the developer's own file, so a block they already changed is left alone.
        if ($file === '.env' && ! str_contains($contents, $search)) {
            continue;
        }

        if (substr_count($contents, $search) !== 1) {
            fwrite(STDERR, "Could not configure {$databases[$database]}: {$file} has changed since this script was written.".PHP_EOL);

            exit(1);
        }

        $contents = str_replace($search, $replace, $contents);
    }

    file_put_contents($path, $contents);
}

unlink(__FILE__);

info("Configured {$databases[$database]} as the database.");

$composer = getenv('COMPOSER_BINARY');

passthru(
    ($composer ? escapeshellarg(PHP_BINARY).' '.escapeshellarg($composer) : 'composer').' run-script setup:database',
    $exitCode,
);

exit($exitCode);
