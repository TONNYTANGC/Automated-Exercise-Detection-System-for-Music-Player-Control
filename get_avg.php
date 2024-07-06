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

// Fetch average values from healthavg table
$sql = "SELECT avgHeartRate, avgspo2, avgTemperature, relayState, controlMode FROM healthavg ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data
    $row = $result->fetch_assoc();
    $avgHeartRate = $row['avgHeartRate'];
    $avgspo2 = $row['avgspo2'];
    $avgTemperature = $row['avgTemperature'];
    $relayState = $row['relayState'];
    $controlMode = $row['controlMode'];

    // Return data as JSON
    echo json_encode(
        array(
            'avgHeartRate' => $avgHeartRate,
            'avgspo2' => $avgspo2,
            'avgTemperature' => $avgTemperature,
            'relayState' => $relayState,
            'controlMode' => $controlMode,
        )
    );
} else {
    echo json_encode(array('error' => 'No data found'));
}

$conn->close();
?>