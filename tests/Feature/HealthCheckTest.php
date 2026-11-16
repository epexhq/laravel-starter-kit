<?php

use function Pest\Laravel\get;

beforeEach(function (): void {
    config(['app.debug' => false]);
});

afterEach(function (): void {
    // RefreshDatabase rolls back the default connection after each test.
    config(['database.default' => 'pgsql']);
});

test('the health route is up when its services are reachable', function (): void {
    get('/up')->assertOk();
});

test('the health route is down when a service it needs is unreachable', function (array $config): void {
    config()->set($config);

    get('/up')->assertInternalServerError();
})->with([
    'database' => [['database.default' => 'unreachable', 'database.connections.unreachable' => ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'laravel']]],
    'cache store' => [['cache.default' => 'database', 'cache.stores.database.table' => 'missing_table']],
    'session store' => [['session.driver' => 'database', 'session.table' => 'missing_table']],
]);
