<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Helper functions
function getTeamOptions() {
    global $conn;
    $sql = "SELECT team_id, team_name FROM Teams";
    $result = $conn->query($sql);
    $options = "";
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $options .= "<option value='" . $row['team_id'] . "'>" . $row['team_name'] . "</option>";
        }
    }
    return $options;
}

function getCoachOptions() {
    global $conn;
    $sql = "SELECT coach_id, coach_name FROM Coaches";
    $result = $conn->query($sql);
    $options = "";
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $options .= "<option value='" . $row['coach_id'] . "'>" . $row['coach_name'] . "</option>";
        }
    }
    return $options;
}

function getPlayerOptions() {
    global $conn;
    $sql = "SELECT player_id, name FROM Players";
    $result = $conn->query($sql);
    $options = "";
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $options .= "<option value='" . $row['player_id'] . "'>" . $row['name'] . "</option>";
        }
    }
    return $options;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Auction System - Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
        h1, h2 { color: #333; }
        form { margin-bottom: 20px; }
        input, select { margin: 5px 0; padding: 5px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <h1>IPL Auction System - Admin Panel</h1>

    <?php
    if (!empty($message)) {
        echo "<p>$message</p>";
    }
    ?>

    <h2>Add Team</h2>
    <form action="admin_panel.php" method="post">
        <input type="text" name="team_name" placeholder="Team Name" required>
        <input type="text" name="city" placeholder="City" required>
        <input type="text" name="home_ground" placeholder="Home Ground">
        <input type="text" name="team_logo" placeholder="Team Logo URL">
        <input type="number" name="budget" placeholder="Budget">
        <input type="number" name="championships_won" placeholder="Championships Won">
        <button type="submit" name="add_team">Add Team</button>
    </form>

    <h2>Add Coach</h2>
    <form action="admin_panel.php" method="post">
        <input type="text" name="coach_name" placeholder="Coach Name" required>
        <input type="text" name="img" placeholder="Image URL">

        <select name="team_id" required>
            <option value="">Select Team</option>
            <?php echo getTeamOptions(); ?>
        </select>
        <button type="submit" name="add_coach">Add Coach</button>
    </form>

    <h2>Add Player</h2>
    <form action="admin_panel.php" method="post">
        <input type="text" name="name" placeholder="Player Name" required>
        <input type="text" name="player_img" placeholder="Player Image URL">
        <input type="number" name="age" placeholder="Age">
        <input type="text" name="nationality" placeholder="Nationality">
        <select name="category" required>
            <option value="Batsman">Batsman</option>
            <option value="Bowler">Bowler</option>
            <option value="All-rounder">All-rounder</option>
            <option value="Wicketkeeper">Wicketkeeper</option>
        </select>
        <input type="number" name="sold_price" placeholder="Sold Price">
        <select name="Iscapped">
            <option value="1">Capped</option>
            <option value="0">Uncapped</option>
        </select>
        <select name="sold_status">
            <option value="1">Sold</option>
            <option value="0">Unsold</option>
        </select>
        <input type="number" name="matches_played" placeholder="Matches Played">
        <input type="number" name="not_out" placeholder="Not Out">
        <input type="number" name="runs" placeholder="Runs">
        <input type="number" name="balls_faced" placeholder="Balls Faced">
        <input type="number" name="wickets" placeholder="Wickets">
        <select name="team_id">
            <option value="">Select Team</option>
            <?php echo getTeamOptions(); ?>
        </select>
        <button type="submit" name="add_player">Add Player</button>
    </form>

    <h2>Update Player Stats</h2>
    <form action="admin_panel.php" method="post">
        <select name="player_id" required>
            <option value="">Select Player</option>
            <?php echo getPlayerOptions(); ?>
        </select>
        <input type="number" name="matches_played" placeholder="Matches Played">
        <input type="number" name="not_out" placeholder="Not Out">
        <input type="number" name="runs" placeholder="Runs">
        <input type="number" name="balls_faced" placeholder="Balls Faced">
        <input type="number" name="wickets" placeholder="Wickets">
        <button type="submit" name="update_player_stats">Update Stats</button>
    </form>

    <h2>Delete Team</h2>
    <form action="admin_panel.php" method="post">
        <select name="team_id" required>
            <option value="">Select Team</option>
            <?php echo getTeamOptions(); ?>
        </select>
        <button type="submit" name="delete_team">Delete Team</button>
    </form>

    <h2>Delete Coach</h2>
    <form action="admin_panel.php" method="post">
        <select name="coach_id" required>
            <option value="">Select Coach</option>
            <?php echo getCoachOptions(); ?>
        </select>
        <button type="submit" name="delete_coach">Delete Coach</button>
    </form>

    <h2>Delete Player</h2>
    <form action="admin_panel.php" method="post">
        <select name="player_id" required>
            <option value="">Select Player</option>
            <?php echo getPlayerOptions(); ?>
        </select>
        <button type="submit" name="delete_player">Delete Player</button>
    </form>
</body>
</html>

<?php
$conn->close();
?>