<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    |
    | Only the values that differ from Laravel's defaults: the cookie is named
    | after the app, and session data is serialized as JSON. Sessions are
    | stored in the database (SESSION_DRIVER).
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session',
    ),

    'serialization' => 'json',

];
