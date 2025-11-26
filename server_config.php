<?php
// server_config.php
// Configuration for live deployment

return [
    // Database settings
    'db_host' => 'your_live_db_host',
    'db_name' => 'b33_40185301_rent_and_go',
    'db_user' => 'b33_40185301',
    'db_pass' => '123456',

    // Application settings
    'app_env' => 'production',
    'app_debug' => false,
    'app_url' => 'https://ayonion-cms.byethost33.com/rent_and_go',

    // Other server-specific settings
    'session_secure' => true,
    'session_http_only' => true,
    'session_same_site' => 'Strict',
];
