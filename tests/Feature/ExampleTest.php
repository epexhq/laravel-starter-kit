<?php

use function Pest\Laravel\get;

test('returns a successful response', function (): void {
    get(route('home'))->assertOk();
});
