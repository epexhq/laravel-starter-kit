<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Credentials for the third party services the app uses.
    |
    */

    'fathom' => [
        'site_id' => env('FATHOM_SITE_ID'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

];
