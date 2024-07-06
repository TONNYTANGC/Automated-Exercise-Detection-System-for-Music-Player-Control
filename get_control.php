<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(array('error' => 'Connection failed: ' . $conn->connect_error)));
}

$sql = "SELECT controlMode, relayState, updated_at FROM control_settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result === FALSE) {
    die(json_encode(array('error' => 'Error: ' . $conn->error)));
}

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$conn->close();
?>