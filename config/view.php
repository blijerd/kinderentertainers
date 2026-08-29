<?php

require_once dirname(__DIR__).'/bootstrap/compiled_views.php';

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Compiled Blade output lives under a build-generation subdirectory so a
    | rolling Coolify deploy cannot let the new image overwrite templates that
    | the previous image still executes from shared storage.
    |
    */

    'compiled' => kinderentertainers_compiled_views_path(),

];
