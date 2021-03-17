<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Remotes
    |--------------------------------------------------------------------------
    |
    | Define on or more remotes you want to sync with.
    | Each remote is an array with 'user', 'host' and 'root'.
    |
    */

    'remotes' => [

        'production' => [
            'user' => 'ploi',
            'host' => '138.68.109.74',
            'port' => 22,
            'root' => '/home/ploi/monsun.theater',
         ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Recipes
    |--------------------------------------------------------------------------
    |
    | Define one or more recipes with the paths you want to sync.
    | Each recipe is an array of relative paths to your project's root.
    |
    */

    'recipes' => [

        'assets' => ['public/assets/aktuelles', 'public/assets/extras', 'public/assets/kuenstler-innen', '/public/assets/produktionen', '/public/assets/monsun-team', 'storage/app/img/'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | An array of default rsync options.
    | You can override these options when executing the command.
    |
    */

    'options' => [
        // '--archive',
        // '--itemize-changes',
        // '--verbose',
        // '--human-readable',
        // '--progress'
    ],

];
