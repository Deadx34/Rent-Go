<?php
$servername = "sql104.byethost33.com"; 

$username = "b33_40185301"; 

$password = "123456"; 

$dbname = "b33_40185301_rent_and_go"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>