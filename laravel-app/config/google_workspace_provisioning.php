<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared / Unit Account Keywords
    |--------------------------------------------------------------------------
    |
    | Local-part substrings (before the @) that identify a department/unit
    | or otherwise shared mailbox rather than a personal employee account.
    | Matching accounts are never auto-created by the provisioning command;
    | they are only reported so an Admin HR can decide how to handle them.
    |
    */

    'shared_unit_keywords' => [
        'webmaster',
        'arsip',
        'baak',
        'kemahasiswaan',
        'kepegawaian',
        'kerma',
        'lppm',
        'media',
        'perpustakaan',
        'pmb',
        'rpl',
        'sentraki',
        'mng',
        'diaas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Seed List
    |--------------------------------------------------------------------------
    |
    | Newline-delimited file of Google Workspace emails used when the
    | provisioning command is run without --emails or --file.
    |
    */

    'seed_list_path' => resource_path('data/google-workspace-emails.txt'),

];
