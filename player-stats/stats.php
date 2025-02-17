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

// Get player_id from URL parameter
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;

// Fetch player details
$player_sql = "SELECT p.*, t.team_name 
               FROM Players p 
               LEFT JOIN Teams t ON p.team_id = t.team_id 
               WHERE p.player_id = $player_id";
$player_result = $conn->query($player_sql);
$player = $player_result->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <title>IPL Auction System - Player Statistics</title>
    <link rel="stylesheet" href="stats.css">
</head>

<body>
    <header>
        <nav>
            <ul>
                <!-- <li><a href="#home">Home</a></li> -->
                <li><a href="../teams/teams.php">Home</a></li>
                <li><a href="../allPlayers/allPlayer.php">Players</a></li>

            </ul>
        </nav>
    </header>
    <div class="player-stats-container">
        <h2>Player Statistics</h2>
        <div class="player-info">
            <img src="<?php echo $player['player_img']; ?>" alt="<?php echo $player['name']; ?>">
            <div class="player-team">
                <h3 id="player-name"><?php echo $player['name']; ?></h3>
                <p id="player-team"><?php echo $player['team_name']; ?></p>
            </div>
        </div>
        <div class="stats-table">
            <table>
                <tr>
                    <th>Category</th>
                    <th>Statistics</th>
                </tr>
                <tr>
                    <td>Age</td>
                    <td><?php echo $player['age']; ?></td>
                </tr>
                <tr>
                    <td>Nationality</td>
                    <td><?php echo $player['nationality']; ?></td>
                </tr>
                <tr>
                    <td>Category</td>
                    <td><?php echo $player['category']; ?></td>
                </tr>
                <tr>
                    <td>Matches Played</td>
                    <td><?php echo $player['matches_played']; ?></td>
                </tr>
                <tr>
                    <td>Runs Scored</td>
                    <td><?php echo $player['runs']; ?></td>
                </tr>
                <tr>
                    <td>Wickets Taken</td>
                    <td><?php echo $player['wickets']; ?></td>
                </tr>
                <tr>
                    <td>Batting Average</td>
                    <td><?php echo number_format($player['batting_avg'], 2); ?></td>
                </tr>
                <tr>
                    <td>Bowling Average</td>
                    <td><?php echo number_format($player['bowling_avg'], 2); ?></td>
                </tr>
                <tr>
                    <td>Strike Rate</td>
                    <td><?php echo number_format($player['strike_rate'], 2); ?></td>
                </tr>
            </table>
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