<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mondial Relay API credentials
    |--------------------------------------------------------------------------
    |
    | Here you may specify the Mondial Relay API credentials that should be used
    | to generate labels. You can obtain these credentials by signing up for
    | an account at https://connect.mondialrelay.com/CC20F8VA/ConfigurationAPI  with the appropriate plan.
    | Production url : https://connect-api.mondialrelay.com/api/Shipment
    | Test url : https://connect-api-sandbox.mondialrelay.com/api/shipment
    */

    'url' => env('MONDIAL_RELAY_API_URL', 'https://connect-api.mondialrelay.com/api/Shipment'),
    'brand_id_api' => env('MONDIAL_RELAY_API_BRAND_ID_API'),
    'login' => env('MONDIAL_RELAY_API_LOGIN'),
    'password' => env('MONDIAL_RELAY_API_PASSWORD'),
];
