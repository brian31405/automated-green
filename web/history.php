<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Data</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>
    <h1>Historical Data</h1>
    <h2>Data Chart</h2>
    <canvas id="myChart" width="400" height="60"></canvas>
    <h2>Data Table</h2>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Air Temperature (°C)</th>
                <th>Air Humidity (%)</th>
                <th>Soil Humidity (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Database configuration
            $servername = "localhost";
            $username = "root";
            $password = "ubuntu";
            $dbname = "history";

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $limit = 25;
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get total number of rows
            $total_result = $conn->query("SELECT COUNT(*) AS count FROM dataload");
            $total_rows = $total_result->fetch_assoc()['count'];
            $total_pages = ceil($total_rows / $limit);

            // Get data for current page
            $sql = "SELECT * FROM dataload ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
            $result = $conn->query($sql);

            $timestamps = [];
            $air_temps = [];
            $air_hums = [];
            $soil_hums = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['timestamp'] . "</td>";
                    echo "<td>" . $row['air_temp'] . "</td>";
                    echo "<td>" . $row['air_hum'] . "</td>";
                    echo "<td>" . $row['soil_hum'] . "</td>";
                    echo "</tr>";

                    $timestamps[] = $row['timestamp'];
                    $air_temps[] = $row['air_temp'];
                    $air_hums[] = $row['air_hum'];
                    $soil_hums[] = $row['soil_hum'];
                }
            } else {
                echo "<tr><td colspan='4'>No data available</td></tr>";
            }

            $conn->close();
            ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php
        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<a href='?page=$i'>$i</a> ";
        }
        ?>
    </div>
    <script>
        // Convert timestamps to readable format for Chart.js
        var timestamps = <?php echo json_encode(array_map(function($timestamp) {
            return (new DateTime($timestamp))->format('Y-m-d H:i:s');
        }, $timestamps)); ?>;
        
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Air Temperature (°C)',
                    data: <?php echo json_encode($air_temps); ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    fill: false
                }, {
                    label: 'Air Humidity (%)',
                    data: <?php echo json_encode($air_hums); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: false
                }, {
                    label: 'Soil Humidity (%)',
                    data: <?php echo json_encode($soil_hums); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute',
                            tooltipFormat: 'YYYY-MM-DD HH:mm:ss'
                        },
                        title: {
                            display: true,
                            text: 'Timestamp'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Value'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>

