<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thinkion API Configuration
    |--------------------------------------------------------------------------
    |
    | Credenciales y parámetros de conexión a la API de reportes de Thinkion.
    | El endpoint final se construye como:
    |   https://{client_code}.thinkerp.cc/online/reporting/public/
    |
    */

    'api' => [
        'client_code' => env('THINKION_CLIENT_CODE', 'tem9'),
        'base_url' => env('THINKION_API_BASE_URL'),  // Override manual; si está vacío se construye desde client_code
        'token' => env('THINKION_API_TOKEN', ''),
        'timeout' => (int) env('THINKION_API_TIMEOUT', 60),
        'retries' => (int) env('THINKION_API_RETRIES', 3),
        'retry_sleep_ms' => (int) env('THINKION_API_RETRY_SLEEP_MS', 1000),
        'max_days_per_request' => (int) env('THINKION_MAX_DAYS_PER_REQUEST', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Defaults
    |--------------------------------------------------------------------------
    |
    | Valores por defecto para la sincronización diaria automática.
    |
    */

    'sync' => [
        'default_report_ids' => env('THINKION_DEFAULT_REPORT_IDS')
            ? array_map('intval', explode(',', env('THINKION_DEFAULT_REPORT_IDS')))
            : [233],

        'default_establishments' => env('THINKION_DEFAULT_ESTABLISHMENTS')
            ? array_map('intval', explode(',', env('THINKION_DEFAULT_ESTABLISHMENTS')))
            : [1, 2],

        'days_back' => (int) env('THINKION_SYNC_DAYS_BACK', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'requests' => (bool) env('THINKION_LOG_REQUESTS', true),
        'responses' => (bool) env('THINKION_LOG_RESPONSES', false),
    ],

];
