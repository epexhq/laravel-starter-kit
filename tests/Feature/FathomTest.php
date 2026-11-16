<?php

use function Pest\Laravel\get;

test('adds the fathom script when a site id is set and debug is off', function (): void {
    config(['services.fathom.site_id' => 'ABCDEFGH', 'app.debug' => false]);

    get(route('home'))->assertOk()->assertSeeHtml('<script src="https://cdn.usefathom.com/script.js" data-site="ABCDEFGH" data-spa="auto" defer></script>');
});

test('leaves the fathom script out', function (?string $siteId, bool $debug): void {
    config(['services.fathom.site_id' => $siteId, 'app.debug' => $debug]);

    get(route('home'))
        ->assertOk()
        ->assertDontSee('cdn.usefathom.com');
})->with([
    'without a site id' => [null, false],
    'with an empty site id' => ['', false],
    'in debug mode' => ['ABCDEFGH', true],
]);
