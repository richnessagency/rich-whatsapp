<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Set to false to fully disable Rich WhatsApp. The package keeps booting,
    | but no bridge requests are made, the dashboard is not registered and the
    | Facade degrades gracefully (see WhatsAppService::enabled()).
    |
    */

    'enabled' => env('RICH_WHATSAPP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Node Bridge connection
    |--------------------------------------------------------------------------
    |
    | RICH_WHATSAPP_BRIDGE_URL  Base URL of the standalone Node.js WhatsApp
    |                           Bridge, without trailing slash.
    |                           Example: https://whatsapp-node.example.com
    | RICH_WHATSAPP_BRIDGE_TOKEN  Bearer token required by the Bridge API.
    |
    | These values are secrets. They are only ever used server-side and are
    | never exposed to browsers, logs or the database.
    |
    */

    'bridge_url' => rtrim((string) env('RICH_WHATSAPP_BRIDGE_URL', ''), '/'),

    'bridge_token' => (string) env('RICH_WHATSAPP_BRIDGE_TOKEN', ''),

    'callback_token' => (string) env('RICH_WHATSAPP_CALLBACK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP client timeouts (seconds)
    |--------------------------------------------------------------------------
    */

    'http_timeout' => (int) env('RICH_WHATSAPP_HTTP_TIMEOUT', 10),

    'connect_timeout' => (int) env('RICH_WHATSAPP_CONNECT_TIMEOUT', 3),

    /*
    |--------------------------------------------------------------------------
    | Message storage
    |--------------------------------------------------------------------------
    |
    | Optional local history used by the dashboard. When disabled, sending
    | still works but nothing is persisted and the dashboard is limited.
    |
    */

    'store_messages' => (bool) env('RICH_WHATSAPP_STORE_MESSAGES', true),

    'store_incoming' => (bool) env('RICH_WHATSAPP_STORE_INCOMING', true),

    'store_outgoing' => (bool) env('RICH_WHATSAPP_STORE_OUTGOING', true),

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media_max_mb' => (int) env('RICH_WHATSAPP_MEDIA_MAX_MB', 10),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard_enabled' => (bool) env('RICH_WHATSAPP_DASHBOARD_ENABLED', true),

    'dashboard_prefix' => (string) env('RICH_WHATSAPP_DASHBOARD_PREFIX', 'whatsapp'),

    'dashboard_middleware' => ['web', 'auth'],

    'dashboard' => [
        // Optional host layout that the package views will extend.
        // Example: 'layouts.admin'. When null a self-contained layout is used.
        'layout' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR / connection flow
    |--------------------------------------------------------------------------
    */

    'qr_poll_seconds' => max(1, (int) env('RICH_WHATSAPP_QR_POLL_SECONDS', 3)),

    /*
    |--------------------------------------------------------------------------
    | Phone normalization
    |--------------------------------------------------------------------------
    */

    'default_country_code' => (string) env('RICH_WHATSAPP_DEFAULT_COUNTRY_CODE', '20'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log_message_content' => (bool) env('RICH_WHATSAPP_LOG_MESSAGE_CONTENT', false),

];