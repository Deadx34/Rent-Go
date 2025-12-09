<?php
require 'db_connect.php';

echo "Checking drivers table structure:\n\n";

$result = $conn->query('DESCRIBE drivers');

if ($result) {
    echo "Column Name | Type | Null | Key | Default\n";
    echo "------------------------------------------------\n";
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Null'] . ' | ' . $row['Key'] . ' | ' . $row['Default'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
