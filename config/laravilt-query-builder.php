<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Settings
    |--------------------------------------------------------------------------
    |
    | Configure your plugin settings here.
    |
    */

    // LARAVILT_QUERY-BUILDER_ENABLED is the pre-1.1 key, still honoured for existing .env files
    'enabled' => env('LARAVILT_QUERY_BUILDER_ENABLED', env('LARAVILT_QUERY-BUILDER_ENABLED', true)),

    // Add your configuration options here
];
