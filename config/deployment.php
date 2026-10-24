<?php

return [

    /*
    | Railway environment name (production, staging, …).
    | Read from config so php artisan config:cache does not blank it.
    */
    'railway_environment' => env('RAILWAY_ENVIRONMENT_NAME', env('RAILWAY_ENVIRONMENT', '')),

    'commit' => env('RAILWAY_GIT_COMMIT_SHA', 'dev'),

];
