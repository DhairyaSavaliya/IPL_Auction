<?php
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

// Add Team
if (isset($_POST['add_team'])) {
    $team_name = $conn->real_escape_string($_POST['team_name']);
    $city = $conn->real_escape_string($_POST['city']);
    $home_ground = $conn->real_escape_string($_POST['home_ground']);
    $team_logo = $conn->real_escape_string($_POST['team_logo']);
    $budget = intval($_POST['budget']);
    $championships_won = intval($_POST['championships_won']);

    $sql = "INSERT INTO Teams (team_name, city, home_ground, team_logo, budget, championships_won) 
            VALUES ('$team_name', '$city', '$home_ground', '$team_logo', $budget, $championships_won)";

    if ($conn->query($sql) === TRUE) {
        echo "New team added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Add Coach
if (isset($_POST['add_coach'])) {
    $coach_name = $conn->real_escape_string($_POST['coach_name']);
    $team_id = intval($_POST['team_id']);
    $img = $conn->real_escape_string($_POST['img']);

    $sql = "INSERT INTO Coaches (coach_name, team_id, img) VALUES ('$coach_name', $team_id, '$img')";


    if ($conn->query($sql) === TRUE) {
        echo "New Coach added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Add Player
if (isset($_POST['add_player'])) {
    $name = $_POST['name'];
    $player_img = $_POST['player_img'];
    $age = $_POST['age'];
    $nationality = $_POST['nationality'];
    $category = $_POST['category'];
    $sold_price = $_POST['sold_price'];
    $Iscapped = $_POST['Iscapped'];
    $sold_status = $_POST['sold_status'];
    $matches_played = $_POST['matches_played'];
    $not_out = $_POST['not_out'];
    $runs = $_POST['runs'];
    $balls_faced = $_POST['balls_faced'];
    $wickets = $_POST['wickets'];
    $team_id = $_POST['team_id'];

    $sql = "INSERT INTO Players (name, player_img, age, nationality, category, sold_price, Iscapped, sold_status, 
            matches_played, not_out, runs, balls_faced, wickets, team_id) 
            VALUES ('$name', '$player_img', $age, '$nationality', '$category', $sold_price, $Iscapped, $sold_status, 
            $matches_played, $not_out, $runs, $balls_faced, $wickets, " . ($team_id ? $team_id : "NULL") . ")";

    if ($conn->query($sql) === TRUE) {
        echo "New player added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Update Player Stats
if (isset($_POST['update_player_stats'])) {
    $player_id = $_POST['player_id'];
    $matches_played = $_POST['matches_played'];
    $not_out = $_POST['not_out'];
    $runs = $_POST['runs'];
    $balls_faced = $_POST['balls_faced'];
    $wickets = $_POST['wickets'];

    $sql = "UPDATE Players SET matches_played = $matches_played, not_out = $not_out, runs = $runs, 
            balls_faced = $balls_faced, wickets = $wickets WHERE player_id = $player_id";

    if ($conn->query($sql) === TRUE) {
        echo "Player stats updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Delete Team
if (isset($_POST['delete_team'])) {
    $team_id = $_POST['team_id'];

    $sql = "DELETE FROM Teams WHERE team_id = $team_id";

    if ($conn->query($sql) === TRUE) {
        echo "Team deleted successfully";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Delete Coach
if (isset($_POST['delete_coach'])) {
    $coach_id = $_POST['coach_id'];

    $sql = "DELETE FROM Coaches WHERE coach_id = $coach_id";

    if ($conn->query($sql) === TRUE) {
        echo "Coach deleted successfully";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Delete Player
if (isset($_POST['delete_player'])) {
    $player_id = $_POST['player_id'];

    $sql = "DELETE FROM Players WHERE player_id = $player_id";

    if ($conn->query($sql) === TRUE) {
        echo "Player deleted successfully";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
// Helper functions to populate select options

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
$conn->close();
?>