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

    /*
    |--------------------------------------------------------------------------
    | Allowed Workspace Domains
    |--------------------------------------------------------------------------
    |
    | Domains eligible for bulk pre-registration by this command. This is a
    | bulk-import filter only — it has no bearing on who may log in. Google
    | SSO login authorization depends solely on whether the verified Google
    | email already exists in the users table (see GoogleSsoService).
    |
    */

    'allowed_domains' => array_values(array_filter(array_unique(array_map(
        static fn (string $domain): string => strtolower(trim($domain)),
        explode(',', (string) env('GOOGLE_WORKSPACE_ALLOWED_DOMAINS', ''))
    )))),

];
