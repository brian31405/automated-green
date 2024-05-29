<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Sensor Data and Pump Control</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }
        .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 50%;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            margin: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<h1>Current Time Sensor Data</h1>
<div class="container">
        <p>
        <?php
        echo date('Y-m-d H:i:s');
        ?>
    </p>
</div>

<table>
    <thead>
        <tr>
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

        // Get latest data
        $sql = "SELECT air_temp, air_hum, soil_hum FROM dataload ORDER BY timestamp DESC LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<tr>";
            echo "<td>" . $row['air_temp'] . "</td>";
            echo "<td>" . $row['air_hum'] . "</td>";
            echo "<td>" . $row['soil_hum'] . "</td>";
            echo "</tr>";
        } else {
            echo "<tr><td colspan='3'>No data available</td></tr>";
        }

        $conn->close();
        ?>
    </tbody>
</table>

<h1>Control Pump</h1>

<?php
// Pump control logic
$servername = "localhost";
$username = "root";
$password = "ubuntu";
$hardware_db = "hardware";

// Create connection to hardware database
$conn = new mysqli($servername, $username, $password, $hardware_db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle toggle pump request
if (isset($_POST['toggle_pump'])) {
    // Get current pump status
    $sql = "SELECT pump FROM run";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $current_status = $row['pump'];

    // Toggle pump status
    $new_status = $current_status == 1 ? 0 : 1;
    $sql = "UPDATE run SET pump = $new_status";
    if ($conn->query($sql) === TRUE) {
        echo "Pump status updated successfully!";
    } else {
        echo "Error updating pump status: " . $conn->error;
    }
}

// Get current pump status for button label
$sql = "SELECT pump FROM run";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$current_status = $row['pump'];

$conn->close();
?>

<form method="post">
    <button type="submit" name="toggle_pump">
        <?php echo $current_status == 1 ? 'Toggle OFF' : 'Toggle ON'; ?>
    </button>
</form>

</body>
</html>

