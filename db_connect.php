<?php
// 1. HOSTNAME: Copy this EXACTLY from your cPanel "MySQL Host Name"
// It is usually NOT "localhost" on ByetHost/InfinityFree.
$servername = "sql302.byethost.com"; 

// 2. USERNAME: This is your cPanel username (from the error: b33_40185301)
$username = "b33_40185301"; 

// 3. PASSWORD: Your hosting account/vPanel password
$password = "123456"; 

// 4. DB NAME: Usually includes your username prefix (e.g., b33_40185301_rent_and_go)
$dbname = "b33_40185301_rent_and_go"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>