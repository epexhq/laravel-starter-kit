<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware. Leave TRUSTED_PROXIES unset on
    | Laravel Cloud, which Laravel then trusts on its own. Amp orbs set it to
    | 127.0.0.1 for the portal's proxy. It takes a comma-separated list of IP
    | addresses or CIDR ranges, or "*".
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
