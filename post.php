<?php

// Database connection parameters
$servername = 'localhost';
$dbname = 'health_monitoring';
$username = 'root';
$password = '';
$api_key_value = 'tPmAT5Ab3j7F9';

// Initialize variables
$api_key = $heartRate = $spo2 = $temperature = "";
// Threshold values
$heartRateThreshold = 120;  //threshold for exercise detection
$spo2Threshold = 95;
$temperatureThreshold = 37.5;

// Function to sanitize input data
function test_input($data)
{
  $data = trim($data);            // Remove whitespace from both sides
  $data = stripslashes($data);    // Remove backslashes
  $data = htmlspecialchars($data); // Convert special characters to HTML entities
  return $data;
}

// Function to calculate the moving average from the database
function calculate_moving_average($conn)
{
  $heartRates = [];
  $spo2Values = [];
  $temperatures = [];

  $query = "SELECT heartRate, spo2, temperature FROM healthraw ORDER BY id DESC LIMIT 10"; // Example: last 10 readings
  $result = $conn->query($query);

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      if ($row['heartRate'] != -999 && $row['spo2'] != -999 && $row['temperature'] != -999) {
        $heartRates[] = (float) $row['heartRate'];
        $spo2Values[] = (float) $row['spo2'];
        $temperatures[] = (float) $row['temperature'];
      }
    }
  }

  $avgHeartRate = array_sum($heartRates) / count($heartRates);
  $avgSpo2 = array_sum($spo2Values) / count($spo2Values);
  $avgTemperature = array_sum($temperatures) / count($temperatures);

  return array($avgHeartRate, $avgSpo2, $avgTemperature);
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Retrieve and sanitize the API key from POST data
  $api_key = test_input($_POST["api_key"]);
  // Verify the API key
  if ($api_key == $api_key_value) {

    // Check if the request is for updating sensor data
    if (isset($_POST["heartRate"]) && isset($_POST["spo2"]) && isset($_POST["temperature"])) {
      // Retrieve and sanitize the data from POST request
      $heartRate = test_input($_POST["heartRate"]);
      $spo2 = test_input($_POST["spo2"]);
      $temperature = test_input($_POST["temperature"]);

      // Create a connection to the database
      $conn = new mysqli($servername, $username, $password, $dbname);
      // Check if the connection failed
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

      // Prepare and bind an SQL statement for inserting data
      $stmt = $conn->prepare("INSERT INTO healthraw (heartRate, spo2, temperature) VALUES (?, ?, ?)");
      $stmt->bind_param("ddd", $heartRate, $spo2, $temperature);

      // Execute the prepared statement
      if ($stmt->execute()) {
        // echo "New record created successfully";
      } else {
        echo "Error: " . $stmt->error;
      }
      $stmt->close();

      // Calculate the moving average and min/max of the collected data
      list($avgHeartRate, $avgSpo2, $avgTemperature) = calculate_moving_average($conn);

      // Fetch the last control mode and relay state
      $query = "SELECT controlMode, relayState FROM healthavg ORDER BY id DESC LIMIT 1";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $controlMode = $row['controlMode'];
        $relayState = $row['relayState'];
      } else {
        $controlMode = "auto";
        $relayState = "OFF";
      }

      // Check automatic control based on averages
      if ($controlMode === 'auto') {
        if ($avgHeartRate > $heartRateThreshold || $avgSpo2 < $spo2Threshold || $avgTemperature > $temperatureThreshold) {
          $relayState = "ON";
        } else {
          $relayState = "OFF";
        }
      }

      // Insert the moving average values into the healthavg table
      $stmt = $conn->prepare("INSERT INTO healthavg (avgHeartRate, avgspo2, avgTemperature, relayState, controlMode) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("dddss", $avgHeartRate, $avgSpo2, $avgTemperature, $relayState, $controlMode);

      if ($stmt->execute()) {
        // echo "Average data record created successfully";
      } else {
        echo "Error: " . $stmt->error;
      }

      // Close the statement and connection
      $stmt->close();
      $conn->close();

      // Return the relay state and control mode to the ESP8266
      echo json_encode(array("relayState" => $relayState, "controlMode" => $controlMode));
    }
  } else {
    error_log("Wrong API Key provided");
    // Output error message for wrong API key
    echo "Wrong API Key provided.";
  }
} else {
  error_log("No data posted with HTTP POST.");
  // Output error message for no data posted
  echo "No data posted with HTTP POST.";
}

?>