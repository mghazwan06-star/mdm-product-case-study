<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | The MDM tools we can import from. Adding one (e.g. Intune) means writing
    | a provider and a translator, then listing it here.
    |
    */

    'providers' => [
        'jamf' => App\Mdm\Jamf\JamfProvider::class,
    ],

    // The provider used by the website's Sync Now button.
    'default_provider' => env('MDM_DEFAULT_PROVIDER', 'jamf'),

    /*
    |--------------------------------------------------------------------------
    | Jamf
    |--------------------------------------------------------------------------
    |
    | The backend reads the Jamf API mock response from this file. In Docker
    | the repo's files/ folder is mounted read-only at /var/www/files.
    |
    */

    'jamf' => [
        'mock_path' => env('MDM_JAMF_MOCK_PATH', base_path('../files/api-mock-response.json')),
    ],

];
