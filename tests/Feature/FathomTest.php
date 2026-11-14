<?php

test('adds the fathom script when a site id is set and debug is off', function (): void {
    config(['services.fathom.site_id' => 'ABCDEFGH', 'app.debug' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<script src="https://cdn.usefathom.com/script.js" data-site="ABCDEFGH" data-spa="auto" defer></script>', escape: false);
});

test('leaves the fathom script out', function (?string $siteId, bool $debug): void {
    config(['services.fathom.site_id' => $siteId, 'app.debug' => $debug]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('cdn.usefathom.com');
})->with([
    'without a site id' => [null, false],
    'with an empty site id' => ['', false],
    'in debug mode' => ['ABCDEFGH', true],
]);
