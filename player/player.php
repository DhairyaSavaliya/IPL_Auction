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

// Get team_id from URL parameter
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

// Fetch team details
$team_sql = "SELECT * FROM Teams WHERE team_id = $team_id";
$team_result = $conn->query($team_sql);
$team = $team_result->fetch_assoc();

// Fetch players
$players_sql = "SELECT * FROM Players WHERE team_id = $team_id";
$players_result = $conn->query($players_sql);

// Fetch coaches
$coaches_sql = "SELECT * FROM Coaches WHERE team_id = $team_id";
$coaches_result = $conn->query($coaches_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $team['team_name']; ?> - IPL Team Page</title>
    <link rel="stylesheet" href="player.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="../teams/teams.php">Home</a></li>
                <li><a href="../allPlayers/allPlayer.php">Players</a></li>
            </ul>
        </nav>
    </header>

    <div class="team-container">
        <div class="team-header">
            <img src="<?php echo $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?> Logo" class="team-logo">
            <h1><?php echo $team['team_name']; ?></h1>
        </div>

        <div class="team-info">
            <p class="team-description"><?php echo $team['team_name']; ?> - Based in <?php echo $team['city']; ?>. Home ground: <?php echo $team['home_ground']; ?>. Championships won: <?php echo $team['championships_won']; ?></p>
        </div>

        <div class="member-list">
            <h2>Players</h2>
            <div class="member-grid">
                <?php
                while ($player = $players_result->fetch_assoc()) {
                    echo '<a href="../player-stats/stats.php?player_id=' . $player['player_id'] . '" class="member-card">';
                    echo '<img src="' . $player['player_img'] . '" alt="' . $player['name'] . '">';
                    echo '<h3>' . $player['name'] . '</h3>';
                    echo '<p>' . $player['category'] . '</p>';
                    echo '</a>';
                }
                ?>
            </div>

            <h2>Coaching Staff</h2>
            <div class="member-grid">
                <?php
                while ($coach = $coaches_result->fetch_assoc()) {
                    echo '<div class="member-card">';
                    echo '<img src="' . $coach['img'] . '" alt="' . $coach['coach_name'] . '">';
                    echo '<h3>' . $coach['coach_name'] . '</h3>';
                    echo '<p>Coach</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 <?php echo $team['team_name']; ?>. All rights reserved.</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>