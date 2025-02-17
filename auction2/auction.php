<?php
// auction.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ipl";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API endpoint to handle bids
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'place_bid') {
        // Add error logging
        error_log("Received bid: " . print_r($_POST, true));
        
        $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $bid_amount = isset($_POST['bid_amount']) ? intval($_POST['bid_amount']) : 0;
        
        if (!$player_id || !$team_id || !$bid_amount) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }
        
        // Verify team exists and has enough budget
        $team_query = "SELECT budget, team_name FROM Teams WHERE team_id = ?";
        $stmt = $conn->prepare($team_query);
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $team_result = $stmt->get_result();
        $team_data = $team_result->fetch_assoc();
        
        if (!$team_data) {
            echo json_encode(['success' => false, 'message' => 'Invalid team']);
            exit;
        }
        
        if ($team_data['budget'] < $bid_amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient budget']);
            exit;
        }
        
        // Get current bid for player
        $player_query = "SELECT current_bid, sold_status FROM Players WHERE player_id = ?";
        $stmt = $conn->prepare($player_query);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $player_result = $stmt->get_result();
        $player_data = $player_result->fetch_assoc();
        
        if (!$player_data) {
            echo json_encode(['success' => false, 'message' => 'Invalid player']);
            exit;
        }
        
        if ($player_data['sold_status'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Player already sold']);
            exit;
        }
        
        $current_bid = intval($player_data['current_bid']);
        if ($bid_amount <= $current_bid) {
            echo json_encode([
                'success' => false, 
                'message' => "Bid amount (₹{$bid_amount}) must be higher than current bid (₹{$current_bid})"
            ]);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update player's current bid and team
            $update_player = "UPDATE Players SET current_bid = ?, current_team_id = ? WHERE player_id = ?";
            $stmt = $conn->prepare($update_player);
            $stmt->bind_param("iii", $bid_amount, $team_id, $player_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update player bid");
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Bid placed successfully',
                'bid_amount' => $bid_amount,
                'team_name' => $team_data['team_name']
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'end_auction') {
        $player_id = intval($_POST['player_id']);
        
        // Get player's current bid and team
        $player_query = "SELECT current_bid, current_team_id FROM Players WHERE player_id = ?";
        $stmt = $conn->prepare($player_query);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $player_result = $stmt->get_result();
        $player_data = $player_result->fetch_assoc();
        
        if (!$player_data || !$player_data['current_team_id']) {
            echo json_encode(['success' => false, 'message' => 'No valid bid found']);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update player as sold
            $update_player = "UPDATE Players SET 
                            sold_status = 1, 
                            sold_price = ?, 
                            team_id = ? 
                            WHERE player_id = ?";
            $stmt = $conn->prepare($update_player);
            $stmt->bind_param("iii", 
                            $player_data['current_bid'], 
                            $player_data['current_team_id'], 
                            $player_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update player status");
            }
            
            // Update team's budget
            $update_team = "UPDATE Teams SET 
                          budget = budget - ? 
                          WHERE team_id = ?";
            $stmt = $conn->prepare($update_team);
            $stmt->bind_param("ii", 
                            $player_data['current_bid'], 
                            $player_data['current_team_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update team budget");
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Auction ended successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Rest of your existing code for displaying players and teams...
$sql = "SELECT p.*, 
        COALESCE(p.current_bid, 0) as current_bid,
        t.team_name as current_team_name
        FROM Players p
        LEFT JOIN Teams t ON p.current_team_id = t.team_id
        WHERE p.sold_status = 0";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$players = array();
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}

// Get teams and their budgets
$sql = "SELECT team_id, team_name, budget FROM Teams";
$teams = $conn->query($sql);
if (!$teams) {
    die("Teams query failed: " . $conn->error);
}

$teams_array = array();
while ($team = $teams->fetch_assoc()) {
    $teams_array[] = $team;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Player Auction</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f0f2f5;
            color: #1a1a1a;
            line-height: 1.6;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        #auction-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .player-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .player-card:hover {
            transform: translateY(-5px);
        }

        .player-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .player-avatar {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1e3c72;
        }

        .player-info h2 {
            color: #1e3c72;
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1e3c72;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .price-info {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .price-box {
            text-align: center;
        }

        .price-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .price-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #2a5298;
        }

        .current-bid {
            background: #e8f5e9;
            padding: 10px 15px;
            border-radius: 10px;
            margin: 15px 0;
        }

        .bid-amount {
            color: #2e7d32;
            font-weight: bold;
        }

        .team-name {
            color: #1e3c72;
            font-weight: 500;
        }

        .bid-form {
            display: grid;
            gap: 10px;
            margin: 15px 0;
            opacity: 0.5;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .auction-control {
            margin-top: 15px;
        }
        .start-auction-btn.in-progress {
            background-color: #666;
        }
        .bid-button {
            background: #1e3c72;
        }
        .bid-button:hover {
            background: #2a5298;
        }

        /* Style for active bid form */
        .bid-form.active {
            opacity: 1;
            pointer-events: auto;
        }
        .team-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            background-color: white;
            cursor: pointer;
        }
        .team-select:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.2);
        }

        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        button {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        button:hover {
            background: #2a5298;
        }

        .timer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #fff3e0;
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .timer i {
            color: #f57c00;
        }

        /* Sold state styling
        .player-card.sold {
            opacity: 0.7;
        } */

        .player-card.sold .bid-form {
            display: none;
        }

        /* .player-card.sold::after {
            content: 'SOLD';
            position: absolute;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        } */

        /*  */
        .timer.warning {
    background: #fff3cd;
    color: #856404;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

@keyframes bidPlaced {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Hide bid form by default */


/* Style for disabled buttons */
button:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

/* Sold state styling */
.player-card.sold {
    opacity: 0.8;
    position: relative;
}

.player-card.sold::after {
    content: 'SOLD';
    position: absolute;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
}
        /*  */

        @media (max-width: 768px) {
            #auction-container {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2em;
            }

            .player-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="header">
        <h1>IPL Player Auction 2024</h1>
        <p>Live Bidding Platform</p>
    </div>
    
    <div id="auction-container">
        <?php if (empty($players)): ?>
            <div style="text-align: center; grid-column: 1/-1; padding: 20px;">
                <h2>No players available for auction at the moment.</h2>
            </div>
        <?php else: ?>
            <?php foreach ($players as $player): ?>
        <div class="player-card" id="player-<?php echo $player['player_id']; ?>">
            <div class="player-header">
                <div class="player-avatar">
                    <!-- <i class="fas fa-user"></i> -->
                     <img style="width:60px; height:60px; border-radius:50%;" src="<?php echo htmlspecialchars($player['player_img']); ?>" alt="">
                </div>
                <div class="player-info">
                    <h2><?php echo htmlspecialchars($player['name']); ?></h2>
                    <span class="category-badge"><?php echo $player['category']; ?></span>
                </div>
            </div>

            <div class="price-info">
                <div class="price-box">
                    <div class="price-label">Starting Bid</div>
                    <div class="price-value">₹0</div>
                </div>
                <div class="price-box">
                    <div class="price-label">Current Bid</div>
                    <div class="price-value bid-amount">₹<?php echo number_format($player['current_bid']); ?></div>
                </div>
            </div>

            <div class="current-bid">
                <i class="fas fa-gavel"></i>
                <span class="team-name">
                    <?php if ($player['current_team_name']): ?>
                        Current Highest Bid by <?php echo htmlspecialchars($player['current_team_name']); ?>
                    <?php else: ?>
                        No bids yet
                    <?php endif; ?>
                </span>
            </div>
            
            <form class="bid-form" onsubmit="placeBid(event, <?php echo $player['player_id']; ?>)">
                        <select name="team_id" required class="team-select">
                            <option value="">Select Your Team</option>
                            <?php foreach ($teams_array as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>" 
                                        data-budget="<?php echo $team['budget']; ?>"
                                        data-name="<?php echo htmlspecialchars($team['team_name']); ?>">
                                    <?php echo htmlspecialchars($team['team_name']); ?> 
                                    (Budget: ₹<?php echo number_format($team['budget']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="bid_amount" 
                               min="<?php echo $player['current_bid'] + 1; ?>" 
                               placeholder="Enter your bid amount"
                               required>
                        <button type="submit" class="bid-button">
                            <i class="fas fa-gavel"></i> Place Bid
                        </button>
                    </form>
            
            <div class="timer" id="timer-<?php echo $player['player_id']; ?>">
                <i class="fas fa-clock"></i>
                <span>Time left: 0:30</span>
            </div>
            
            <div class="auction-control">
                <button class="start-auction-btn" onclick="startAuction(<?php echo $player['player_id']; ?>)">
                    <i class="fas fa-play"></i> Start Auction
                </button>
            </div>
        </div>
    <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <script>
    let timers = {};
    let auctionInProgress = {};
    let currentBidAmount = {};

    function startAuction(playerId) {
        const playerCard = document.getElementById(`player-${playerId}`);
        if (!playerCard) return;
        
        // Check if auction is already in progress
        if (auctionInProgress[playerId]) {
            alert('Auction is already in progress for this player');
            return;
        }
        
        const startButton = playerCard.querySelector('.start-auction-btn');
        const bidForm = playerCard.querySelector('.bid-form');
        
        // Initialize auction state
        auctionInProgress[playerId] = true;
        
        // Get initial bid amount
        const bidAmountElement = playerCard.querySelector('.bid-amount');
        currentBidAmount[playerId] = parseInt(bidAmountElement.textContent.replace(/[^0-9]/g, '')) || 0;
        
        // Update start button
        startButton.disabled = true;
        startButton.innerHTML = '<i class="fas fa-gavel"></i> Auction In Progress';
        startButton.classList.add('in-progress');
        
        // Enable bid form
        bidForm.classList.add('active');
        bidForm.style.opacity = '1';
        bidForm.style.pointerEvents = 'auto';
        bidForm.reset();
        
        // Start timer
        startTimer(playerId);
    }

    function startTimer(playerId) {
        let timeLeft = 30; // 2 minutes in seconds
        const timerElement = document.getElementById(`timer-${playerId}`);
        if (!timerElement) return;
        
        const timerSpan = timerElement.querySelector('span');
        
        if (timers[playerId]) {
            clearInterval(timers[playerId]);
        }
        
        timerSpan.textContent = `Time left: 0:30`;
        timerElement.classList.remove('warning');
        
        timers[playerId] = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerSpan.textContent = `Time left: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 30) {
                timerElement.classList.add('warning');
            }
            
            if (timeLeft <= 0) {
                clearInterval(timers[playerId]);
                delete timers[playerId];
                delete auctionInProgress[playerId];
                endAuction(playerId);
            }
        }, 1000);
    }

    async function placeBid(event, playerId) {
        event.preventDefault();
        
        if (!auctionInProgress[playerId]) {
            alert('Please start the auction first');
            return;
        }
        
        const form = event.target;
        const teamId = form.team_id.value;
        const bidAmount = parseInt(form.bid_amount.value);
        
        // Validate input
        if (!teamId || !bidAmount) {
            alert('Please select a team and enter a bid amount');
            return;
        }
        
        const teamOption = form.team_id.options[form.team_id.selectedIndex];
        const teamBudget = parseInt(teamOption.dataset.budget);
        const teamName = teamOption.dataset.name;
        
        // Validate bid amount against current bid
        if (bidAmount <= currentBidAmount[playerId]) {
            alert(`Bid amount must be higher than current bid of ₹${currentBidAmount[playerId].toLocaleString()}`);
            return;
        }
        
        if (bidAmount > teamBudget) {
            alert(`Bid amount exceeds ${teamName}'s budget of ₹${teamBudget.toLocaleString()}`);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'place_bid');
        formData.append('player_id', playerId);
        formData.append('team_id', teamId);
        formData.append('bid_amount', bidAmount);
        
        try {
            const response = await fetch('auction.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                const playerCard = document.getElementById(`player-${playerId}`);
                const bidValue = playerCard.querySelector('.bid-amount');
                const teamDisplay = playerCard.querySelector('.team-name');
                const bidInput = playerCard.querySelector('input[name="bid_amount"]');
                
                // Update current bid amount in our tracking
                currentBidAmount[playerId] = result.bid_amount;
                
                bidValue.textContent = `₹${result.bid_amount.toLocaleString()}`;
                teamDisplay.textContent = `Current Highest Bid by ${result.team_name}`;
                bidInput.min = result.bid_amount + 1;
                bidInput.value = '';
                
                // Reset timer but maintain auction state
                startTimer(playerId);
                
                playerCard.style.animation = 'bidPlaced 0.5s ease';
                setTimeout(() => {
                    playerCard.style.animation = '';
                }, 500);
                
                // Show success message
                alert(`Bid of ₹${result.bid_amount.toLocaleString()} placed successfully for ${teamName}`);
            } else {
                alert(result.message || 'Error placing bid. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error while placing bid. Please try again.');
        }
    }

    async function endAuction(playerId) {
        const playerCard = document.getElementById(`player-${playerId}`);
        if (!playerCard) return;
        
        try {
            const response = await fetch('auction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'end_auction',
                    player_id: playerId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                playerCard.classList.add('sold');
                
                // Update timer display
                const timerElement = playerCard.querySelector('.timer');
                timerElement.innerHTML = '<i class="fas fa-check-circle"></i> Auction ended';
                timerElement.classList.remove('warning');
                
                // Clear auction state
                if (timers[playerId]) {
                    clearInterval(timers[playerId]);
                    delete timers[playerId];
                }
                delete auctionInProgress[playerId];
                delete currentBidAmount[playerId];
                
                // Update UI elements
                const startButton = playerCard.querySelector('.start-auction-btn');
                startButton.disabled = true;
                startButton.innerHTML = '<i class="fas fa-check"></i> Sold';
                
                // Hide the bid form
                const bidForm = playerCard.querySelector('.bid-form');
                if (bidForm) {
                    bidForm.classList.remove('active');
                    bidForm.style.opacity = '0.5';
                    bidForm.style.pointerEvents = 'none';
                }
            } else {
                alert(result.message || 'Error ending auction. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error while ending auction. Please try again.');
        }
    }
</script>
</body>
</html>