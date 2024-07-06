<?php
// MySQLi database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlMode = isset($_POST['controlMode']) ? $_POST['controlMode'] : 'auto';
    $relayState = isset($_POST['relayState']) ? $_POST['relayState'] : 'OFF';
    
    // Update the latest entry in the healthavg table
    $sql = "UPDATE healthavg SET controlMode = ?, relayState = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $controlMode, $relayState);
    
    if ($stmt->execute()) {
        echo json_encode(array('status' => 'success','relayState' => $relayState, 'controlMode' => $controlMode));
    } else {
        echo json_encode(array('status' => 'error','message' => $stmt->error));
    }

    $stmt->close();
}

$conn->close();
?>
