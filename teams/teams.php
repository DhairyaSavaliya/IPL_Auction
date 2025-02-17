<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ipl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch teams from the database
$sql = "SELECT * FROM Teams";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Teams</title>
    <link rel="stylesheet" href="teams.css">
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="./teams.php">Home</a></li>
                <li><a href="../allPlayers/allPlayer.php">Players</a></li>
            </ul>
        </nav>
    </header>
    <div class="ipl-teams-container">
        <h1>IPL Teams</h1>

        <div class="teams-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<a href="../player/player.php?team_id=' . $row["team_id"] . '">';
                    echo '<div class="team-card">';
                    echo '<img src="' . $row["team_logo"] . '" alt="' . $row["team_name"] . ' Logo" class="team-logo">';
                    echo '<h3>' . $row["team_name"] . '</h3>';
                    echo '</div>';
                    echo '</a>';
                }
            } else {
                echo "No teams found";
            }
            ?>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 IPL. All rights reserved.</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>