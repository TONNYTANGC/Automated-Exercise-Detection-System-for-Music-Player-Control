<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Bootstrap Dashboards">
    <meta name="author" content="Bootstrap Gallery">
    <link rel="shortcut icon" href="img/favicon.svg" />

    <!-- Title -->
    <title>Automated Exercise Detection System Using Heart Rate Sensors for Music Player Control</title>

    <!-- Common Css Files -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="fonts/style.css">
    <link rel="stylesheet" href="css/main.min.css">
    <script src="vendor/apex/apexcharts.min.js"></script>

</head>

<body>

    <div class="container-fluid">
        <div class="main-container">
            <div class="page-header">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Smart Music Player Control Dashboard</li>
                </ol>
            </div>
            <div class="content-wrapper">
                <div class="row gutters">
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="hospital-tiles" id="avgHeartRateTile">
                            <img src="img/heart.svg" alt="avgHeartRate" style="width: 200px; height: auto;" />
                            <p>Average BPM</p>
                            <h2>Updating...</h2>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="hospital-tiles" id="avgSPO2Tile">
                            <img src="img/spo2.png" alt="avgSPO2" style="width: 200px; height: auto;" />
                            <p>Average SPO2</p>
                            <h2>Updating...</h2>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="hospital-tiles" id="avgTempTile">
                            <img src="img/temp.svg" alt="avgTemp" style="width: 200px; height: auto;" />
                            <p>Average Body Temp</p>
                            <h2>Updating...</h2>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-lg-6 col-sm-12 col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Heart Rate (BPM)</div>
                            </div>
                            <div class="card-body">
                                <div id="heart-rate-chart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-12 col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Oxygen Saturation (SPO2%)</div>
                            </div>
                            <div class="card-body">
                                <div id="oxygen-saturation-chart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-12 col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Body Temperature (°C)</div>
                            </div>
                            <div class="card-body">
                                <div id="body-temperature-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-12">
                        <div class="hospital-tiles" id="relayState">
                            <img src="img/music.svg" alt="status" style="width: 500px; height: auto;" />
                            <p>Music Player Status</p>
                            <h2>Updating...</h2>
                            <div>
                                <label for="manualToggle">Manual Control</label>
                                <input type="checkbox" id="manualToggle">
                                <select id="relayStateSelect" disabled>
                                    <option value="ON">ON</option>
                                    <option value="OFF">OFF</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="main-footer"></footer>
        </div>
    </div>

    <!-- Required JavaScript Files -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="vendor/apex/apexcharts.min.js"></script>

    <script>
        $(document).ready(function () {
            let heartRateChart, oxygenSaturationChart, bodyTemperatureChart;

            function fetchHealthData() {
                $.getJSON('get_raw.php', function (data) {
                    let heartRates = data.map(item => item.heartRate);
                    let spo2s = data.map(item => item.spo2);
                    let temperatures = data.map(item => item.temperature);
                    let timestamps = data.map(item => new Date(item.time).toLocaleTimeString());

                    if (heartRateChart && oxygenSaturationChart && bodyTemperatureChart) {
                        heartRateChart.updateSeries([{ data: heartRates }]);
                        heartRateChart.updateOptions({ xaxis: { categories: timestamps } });

                        oxygenSaturationChart.updateSeries([{ data: spo2s }]);
                        oxygenSaturationChart.updateOptions({ xaxis: { categories: timestamps } });

                        bodyTemperatureChart.updateSeries([{ data: temperatures }]);
                        bodyTemperatureChart.updateOptions({ xaxis: { categories: timestamps } });
                    } else {
                        heartRateChart = new ApexCharts(document.querySelector("#heart-rate-chart"), {
                            series: [{ name: 'Heart Rate', data: heartRates }],
                            chart: { type: 'line', height: 350 },
                            xaxis: { categories: timestamps },
                            colors: ['#008FFB'],  // Change color here
                            markers: {
                                size: 6,  // Marker size
                                strokeWidth: 0, // Marker border width
                                hover: { sizeOffset: 2 } // Marker hover size offset
                            }
                        });
                        heartRateChart.render();

                        oxygenSaturationChart = new ApexCharts(document.querySelector("#oxygen-saturation-chart"), {
                            series: [{ name: 'Oxygen Saturation', data: spo2s }],
                            chart: { type: 'line', height: 350 },
                            xaxis: { categories: timestamps },
                            colors: ['#00E396'],  // Change color here
                            markers: {
                                size: 6,  // Marker size
                                strokeWidth: 0, // Marker border width
                                hover: { sizeOffset: 2 } // Marker hover size offset
                            }
                        });
                        oxygenSaturationChart.render();

                        bodyTemperatureChart = new ApexCharts(document.querySelector("#body-temperature-chart"), {
                            series: [{ name: 'Body Temperature', data: temperatures }],
                            chart: { type: 'line', height: 350 },
                            xaxis: { categories: timestamps },
                            colors: ['#FEB019'],  // Change color here
                            markers: {
                                size: 6,  // Marker size
                                strokeWidth: 0, // Marker border width
                                hover: { sizeOffset: 2 } // Marker hover size offset
                            }
                        });
                        bodyTemperatureChart.render();
                    }
                }).fail(function (jqxhr, textStatus, error) {
                    var err = textStatus + ", " + error;
                    console.log("Request Failed: " + err);
                    console.log('Response Text: ' + jqxhr.responseText); // Log the actual response received
                });
            }

            // Fetch health data initially
            fetchHealthData();

            // Fetch health data every 10 seconds
            setInterval(fetchHealthData, 1000);
        });
    </script>

    <script>
        $(document).ready(function () {
            let manualControl = false;
            // Function to fetch average values
            function fetchAverages() {
                $.getJSON('get_avg.php', function (data) {
                    // Update tiles with fetched data
                    $('#avgHeartRateTile h2').text(data.avgHeartRate);
                    $('#avgSPO2Tile h2').text(data.avgspo2);
                    $('#avgTempTile h2').text(data.avgTemperature);
                    // Update manual control checkbox and relay state
                    if (data.controlMode === 'manual') {
                        manualControl = true;
                        $('#manualToggle').prop('checked', true);
                        $('#relayStateSelect').val(data.relayState).prop('disabled', false);
                        $('#relayState h2').text(data.relayState);
                    } else {
                        manualControl = false;
                        $('#manualToggle').prop('checked', false);
                        $('#relayStateSelect').prop('disabled', true);
                        // Update relay state display if not in manual mode
                        $('#relayState h2').text(data.relayState);
                    }


                }).fail(function (jqxhr, textStatus, error) {
                    var err = textStatus + ", " + error;
                    console.log("Request Failed: " + err);
                    console.log('Response Text: ' + jqxhr.responseText); // Log the actual response received
                });
            }


            // Call fetchAveragesAndSend initially
            fetchAverages();
            setInterval(fetchAverages, 1000);

            // Handle manual toggle switch change
            $('#manualToggle').change(function () {
                manualControl = this.checked;
                $('#relayStateSelect').prop('disabled', !manualControl);
                if (manualControl) {
                    updateRelayState($('#relayStateSelect').val(), 'manual');
                } else {
                    updateRelayState('OFF', 'auto');
                }
            });

            // Handle relay state selection change
            $('#relayStateSelect').change(function () {
                if (manualControl) {
                    updateRelayState(this.value, 'manual');
                }
            });

            // Function to update relay state
            function updateRelayState(state, controlMode) {
                $.post('update_relay.php', { relayState: state, controlMode: controlMode }, function (response) {
                    console.log("Response from update_relay.php:", response);

                    if (response.status === 'success') {
                        $('#relayState h2').text(response.relayState);
                    } else {
                        console.log("Error updating relay state:", response.message);
                        if (response.debug_log) {
                            console.log("Debug log:", response.debug_log);
                        }
                    }
                }, 'json').fail(function (jqxhr, textStatus, error) {
                    console.log("Request Failed: " + textStatus + ", " + error);
                });
            }
        });
    </script>


</body>

</html>