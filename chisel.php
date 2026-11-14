<?php

/*
 * Chooses the app's database once, when the project is created. `laravel new` runs it through
 * `composer setup:database`, and so does `composer setup`. It writes the choice into the project
 * with Laravel Chisel, deletes itself, and then creates and migrates the databases.
 *
 * Set DB_CONNECTION to pgsql, mysql or sqlite to choose without a prompt. Without a prompt
 * and without DB_CONNECTION, PostgreSQL is kept.
 */

use Laravel\Chisel\Chisel;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

require __DIR__.'/vendor/autoload.php';

$databases = ['pgsql' => 'PostgreSQL', 'mysql' => 'MySQL', 'sqlite' => 'SQLite'];

$database = getenv('DB_CONNECTION') ?: select('Which database will your application use?', $databases, default: 'pgsql');

if (! is_string($database) || ! isset($databases[$database])) {
    fwrite(STDERR, 'DB_CONNECTION must be one of: '.implode(', ', array_keys($databases)).PHP_EOL);

    exit(1);
}

/**
 * Chisel's replace() skips text it doesn't find. Fail instead, so a file that changed since
 * this script was written can't leave the project half configured.
 */
function chiselReplace(Chisel $chisel, string $file, string $search, string $replace): void
{
    if (substr_count((string) file_get_contents(__DIR__.'/'.$file), $search) !== 1) {
        throw new RuntimeException("chisel.php could not find the text it replaces in {$file}.");
    }

    $chisel->file($file)->replace($search, $replace);
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

$testDatabaseScript = <<<'JSON'
            "setup:test-database": [
                "@putenv DB_DATABASE=testing",
                "@php artisan migrate --force --graceful --ansi"
            ],

    JSON;

$script = Chisel::script(__DIR__)
    ->selected('database', 'pgsql', else: function (Chisel $chisel) use ($database, $environment): void {
        chiselReplace(
            $chisel,
            'config/database.php',
            "'default' => env('DB_CONNECTION', 'pgsql'),",
            "'default' => env('DB_CONNECTION', '{$database}'),",
        );

        chiselReplace($chisel, '.env.example', $environment['pgsql'], $environment[$database]);

        // .env is the developer's own file, so a block they already changed is left alone.
        if (file_exists(__DIR__.'/.env') && str_contains((string) file_get_contents(__DIR__.'/.env'), $environment['pgsql'])) {
            $chisel->file('.env')->replace($environment['pgsql'], $environment[$database]);
        }

        chiselReplace(
            $chisel,
            'phpunit.xml',
            '<env name="DB_CONNECTION" value="pgsql"/>',
            "<env name=\"DB_CONNECTION\" value=\"{$database}\"/>",
        );

        // The CI workflows start their database with this action. Chisel can't copy files.
        rename(
            __DIR__."/.github/actions/setup-database/{$database}.yml",
            __DIR__.'/.github/actions/setup-database/action.yml',
        );
    })
    ->selected(
        'database',
        'sqlite',
        then: function (Chisel $chisel) use ($testDatabaseScript): void {
            chiselReplace(
                $chisel,
                'phpunit.xml',
                '<env name="DB_DATABASE" value="testing"/>',
                '<env name="DB_DATABASE" value=":memory:"/>',
            );

            chiselReplace($chisel, 'composer.json', '"@php chisel.php"', '"@php artisan migrate --force --graceful --ansi"');
            chiselReplace($chisel, 'composer.json', $testDatabaseScript, '');
        },
        else: function (Chisel $chisel): void {
            chiselReplace(
                $chisel,
                'composer.json',
                '"@php chisel.php"',
                '"@php artisan migrate --force --graceful --ansi",'."\n".'            "@composer run-script setup:test-database"',
            );
        },
    )
    ->apply(function (Chisel $chisel): void {
        $chisel->files(
            '.github/actions/setup-database/mysql.yml',
            '.github/actions/setup-database/sqlite.yml',
            'chisel.php',
        )->delete();
    });

try {
    $script->chisel(['database' => $database]);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL.'The project may be partially configured; review the changes before continuing.'.PHP_EOL);

    exit(1);
}

info("Configured {$databases[$database]} as the database.");

$composer = getenv('COMPOSER_BINARY');

passthru(
    ($composer ? escapeshellarg(PHP_BINARY).' '.escapeshellarg($composer) : 'composer').' run-script setup:database',
    $exitCode,
);

exit($exitCode);
