<?php
// server_config.php
// Configuration for live deployment

return [
    // Database settings
    'db_host' => 'your_live_db_host',
    'db_name' => 'your_live_db_name',
    'db_user' => 'your_live_db_user',
    'db_pass' => 'your_live_db_password',

    // Application settings
    'app_env' => 'production',
    'app_debug' => false,
    'app_url' => 'https://ayonion-cms.byethost33.com/rent_and_go/',

    // Other server-specific settings
    'session_secure' => true,
    'session_http_only' => true,
    'session_same_site' => 'Strict',
];
