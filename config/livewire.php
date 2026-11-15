<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Only the values that differ from Livewire's defaults. Components are
    | class-based: the class in app/Livewire, where Rector, Pint and Larastan
    | check it, and its view in resources/views/livewire. make:livewire
    | creates that kind, and view-based (single-file and multi-file)
    | components aren't looked up. Layouts stay in resources/views/layouts.
    |
    */

    'component_locations' => [],

    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
    ],

    'make_command' => [
        'type' => 'class',
        'emoji' => false,
        'with' => [
            'js' => false,
            'css' => false,
            'test' => false,
        ],
    ],

];
