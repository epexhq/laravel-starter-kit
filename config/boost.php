<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Test Enforcement Guidelines
    |--------------------------------------------------------------------------
    |
    | Without this option, Boost adds its test enforcement guidelines when
    | "artisan test --list-tests" lists at least six tests. With Pest's Test
    | Impact Analysis enabled locally, that command lists only the affected
    | tests, so the generated AGENTS.md would change from run to run.
    |
    */

    'enforce_tests' => true,

];
