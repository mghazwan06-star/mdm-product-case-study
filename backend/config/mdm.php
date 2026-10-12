<?php

return [

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
