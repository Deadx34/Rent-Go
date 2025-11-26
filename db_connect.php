<?php
$servername = "localhost";
$username = "root"; // Change this to your cPanel DB username
$password = "";     // Change this to your cPanel DB password
$dbname = "rent_and_go"; // Change this to your DB name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>