<?php

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormEntry;

return [
    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you want to use your own models, you can define them here.
    | They must extend the original models.
    |
    */
    'models' => [
        'form' => CustomForm::class,
        'entry' => CustomFormEntry::class,
        'user' => config('auth.providers.users.model') ?? 'App\Models\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Options
    |--------------------------------------------------------------------------
    |
    | Define the models and enums that can be linked to form fields dynamically.
    |
    */
    'field_options' => [
        'models' => [
            'App\Models\User' => 'Users',
        ],
        'enums' => [
            // 'App\Enums\StatusEnum' => 'Statuses',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Define the disk and directory where file uploads should be stored.
    |
    */
    'uploads' => [
        'disk' => 'public',
        'directory' => 'custom-form-uploads',
        'visibility' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Customize the navigation icons and groups.
    |
    */
    'navigation' => [
        'group' => 'Form Builder',
        'entry_group' => 'Form Entry',
        'icon' => 'heroicon-o-rectangle-stack',
        'entry_icon' => 'heroicon-o-document-text',
        'dynamic_navigation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Define the locales that should be available for translations.
    |
    */
    'locales' => ['en', 'km'],
];
