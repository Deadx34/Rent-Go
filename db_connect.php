<?php
// Load config if available, otherwise fall back to hard-coded defaults
$configFile = __DIR__ . '/server_config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    $servername = $config['db_host'] ?? 'localhost';
    $username = $config['db_user'] ?? 'b33_40185301'; // Change this to your cPanel DB username
    $password = $config['db_pass'] ?? '123456';      // Change this to your cPanel DB password
    $dbname = $config['db_name'] ?? 'b33_40185301_rent_and_go'; // Change this to your DB name
} else {
    $servername = "localhost";
    $username = "b33_40185301"; // Change this to your cPanel DB username
    $password = "123456";     // Change this to your cPanel DB password
    $dbname = "b33_40185301_rent_and_go"; // Change this to your DB name
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}