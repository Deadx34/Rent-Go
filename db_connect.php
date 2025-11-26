<?php
$servername = "localhost";
$username = "b33_40185301"; // Change this to your cPanel DB username
$password = "123456";     // Change this to your cPanel DB password
$dbname = "b33_40185301_rent_and_go"; // Change this to your DB name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
<?php
$config = require __DIR__ . '/server_config.php';

$servername = $config['db_host'];
$username = $config['db_user'];
$password = $config['db_pass'];
$dbname = $config['db_name'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}