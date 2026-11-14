<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Only the values that differ from Laravel's defaults: the file store also
    | keeps its locks under storage/framework/cache, and cached values may not
    | unserialize objects. The app caches in the database (CACHE_STORE).
    |
    */

    'stores' => [

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/locks'),
        ],

    ],

    'serializable_classes' => false,

];
