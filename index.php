<?php
// MySQL Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pgweb8";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $conn->real_escape_string($_GET['delete_id']);
    $delete_sql = "DELETE FROM tabelpenduduk_pgweb8 WHERE kecamatan = '$delete_id'";
    $conn->query($delete_sql);
    header("Location: index.php");
    exit();
}

// Initialize edit form variables
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $conn->real_escape_string($_GET['edit_id']);
    $edit_sql = "SELECT * FROM tabelpenduduk_pgweb8 WHERE kecamatan = '$edit_id'";
    $result = $conn->query($edit_sql);

    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Update data if edit form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Get data from the form
    $kecamatan = $_POST['kecamatan'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];
    $luas = $_POST['luas'];
    $jumlah_penduduk = $_POST['jumlah_penduduk'];

    // Update query using unique identifier (in this case, kecamatan)
    $update_sql = "UPDATE tabelpenduduk_pgweb8 SET 
                    longitude='$longitude', 
                    latitude='$latitude', 
                    luas='$luas', 
                    jumlah_penduduk='$jumlah_penduduk' 
                    WHERE kecamatan='$kecamatan'";
    
    if ($conn->query($update_sql) === TRUE) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . $update_sql . "<br>" . $conn->error;
    }
}

// Fetch data for the map
$sql = "SELECT kecamatan, longitude, latitude FROM tabelpenduduk_pgweb8";
$result = $conn->query($sql);
$markers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $markers[] = [
            "kecamatan" => $row["kecamatan"],
            "longitude" => $row["longitude"],
            "latitude" => $row["latitude"]
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web GIS Sleman</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* CSS styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .navbar {
            background-color: #FFB26F;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }
        #map {
            width: 100%;
            height: 50vh;
            margin-bottom: 20px;
        }
        #table-container {
            width: 90%;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            overflow: hidden; /* Rounded corners applied */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #FFCF81; /* Table header background */
            color: white; /* Header text color */
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2; /* Zebra striping for even rows */
        }
        tr:hover {
            background-color: #e0f7fa; /* Hover effect */
        }
        .edit-link, .delete-link {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
        }
        .edit-link:hover, .delete-link:hover {
            text-decoration: underline; /* Underline on hover */
        }
        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; /* Increase z-index to be above the map */
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
            align-items: center; /* Centering the modal vertically */
            justify-content: center; /* Centering the modal horizontally */
            display: flex; /* Use flexbox for centering */
        }
        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 500px; /* Set a max width for the modal */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="navbar">Web GIS Sleman</div>

    <!-- Leaflet Map -->
    <div id="map"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map("map").setView([-7.8753849, 110.4262088], 10);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);

        var markers = <?php echo json_encode($markers); ?>;
        markers.forEach(function(data) {
            L.marker([data.latitude, data.longitude]).addTo(map)
                .bindPopup("<b>" + data.kecamatan + "</b><br> Koordinat: (" + data.latitude + ", " + data.longitude + ")");
        });

        function openEditModal(data) {
            document.getElementById('kecamatan').value = data.kecamatan; // Set the kecamatan input
            document.getElementById('longitude').value = data.longitude;
            document.getElementById('latitude').value = data.latitude;
            document.getElementById('luas').value = data.luas;
            document.getElementById('jumlah_penduduk').value = data.jumlah_penduduk;
            document.getElementById('editModal').style.display = "flex"; // Use flex to center modal
        }

        function closeModal() {
            document.getElementById('editModal').style.display = "none";
        }
    </script>

    <!-- Table -->
    <div id="table-container">
        <?php
        $sql = "SELECT * FROM tabelpenduduk_pgweb8";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>
            <tr>
                <th>Kecamatan</th>
                <th>Longitude</th>
                <th>Latitude</th>
                <th>Luas</th>
                <th>Jumlah Penduduk</th>
                <th>Aksi</th>
            </tr>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                <td>" . $row["kecamatan"] . "</td>
                <td>" . $row["longitude"] . "</td>
                <td>" . $row["Latitude"] . "</td>
                <td>" . $row["luas"] . "</td>
                <td>" . number_format($row["jumlah_penduduk"]) . "</td>
                <td>
                    <span onclick='openEditModal(" . json_encode($row) . ")' class='edit-link'>Edit</span> |
                    <a href='?delete_id=" . $row["kecamatan"] . "' onclick=\"return confirm('Are you sure?');\" class='delete-link'>Delete</a>
                </td>
            </tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='text-align:center;'>No records found.</p>";
        }
        ?>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Data Kecamatan</h2>
            <form action="index.php" method="post">
                <input type="hidden" name="old_kecamatan" id="old_kecamatan">
                <label>Kecamatan:</label>
                <input type="text" name="kecamatan" id="kecamatan" required>
                <label>Longitude:</label>
                <input type="text" name="longitude" id="longitude" required>
                <label>Latitude:</label>
                <input type="text" name="latitude" id="latitude" required>
                <label>Luas:</label>
                <input type="text" name="luas" id="luas" required>
                <label>Jumlah Penduduk:</label>
                <input type="text" name="jumlah_penduduk" id="jumlah_penduduk" required>
                <button type="submit" name="update">Update</button>
            </form>
        </div>
    </div>

</body>
</html>

<?php
$conn->close();
?>

