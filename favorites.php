<?php
session_start();
require_once 'config/database.php'; // Include database configuration

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must log in to access this page.";
    $_SESSION['message_type'] = "error";
    header("Location: auth/login.php");
    exit;
}

// Generate a CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$favorites = [];

try {
    $conn = get_db_connection(); // Assuming this returns a mysqli connection
    // Select all necessary columns directly
    $stmt = $conn->prepare("SELECT game_id, game_name, cover_url, rating, first_release_date FROM favorites WHERE user_id = ? ORDER BY added_at DESC");
    $stmt->bind_param("i", $user_id); // Bind user_id as integer
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set from the prepared statement

    // Fetch results directly into the favorites array
    while ($row = $result->fetch_assoc()) {
        // Sanitize output
        $row['game_name'] = htmlspecialchars($row['game_name']);
        $row['cover_url'] = $row['cover_url'] ? htmlspecialchars($row['cover_url']) : null; // Handle null cover_url
        $favorites[] = $row;
    }
    $stmt->close(); // Close the statement
    $conn->close(); // Close the connection

} catch (mysqli_sql_exception $e) { // Catch mysqli specific exceptions
    // Handle database errors gracefully
    error_log("Database error fetching favorites: " . $e->getMessage());
    // Optionally set an error message for the user
    $_SESSION['message'] = "Could not load favorites due to a database error.";
    $_SESSION['message_type'] = "error";
    $favorites = []; // Ensure favorites is empty on error
} catch (Exception $e) {
    error_log("Error fetching favorites: " . $e->getMessage());
    $_SESSION['message'] = "An unexpected error occurred while loading favorites."; // More generic message
    $_SESSION['message_type'] = "error";
    $favorites = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Favorite Games - Game Curator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/favorites.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap');

    body {
      font-family: 'Orbitron', sans-serif;
      background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
      min-height: 100vh;
    }
    
    .glass {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 1rem;
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .game-card {
      transition: all 0.3s ease;
    }
    
    .game-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .heart-container {
      --heart-color: #a855f7;
      position: relative;
      width: 32px;
      height: 32px;
      transition: .3s;
    }

    .heart-container .checkbox {
      position: absolute;
      width: 100%;
      height: 100%;
      opacity: 0;
      z-index: 20;
      cursor: pointer;
    }

    .heart-container .svg-container {
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .heart-container .svg-outline,
    .heart-container .svg-filled {
      fill: var(--heart-color);
      position: absolute;
    }

    .heart-container .svg-filled {
      animation: keyframes-svg-filled 1s;
      display: none;
    }

    .heart-container .svg-celebrate {
      position: absolute;
      animation: keyframes-svg-celebrate .5s;
      animation-fill-mode: forwards;
      display: none;
      stroke: var(--heart-color);
      fill: var(--heart-color);
      stroke-width: 2px;
    }

    .heart-container .checkbox:checked~.svg-container .svg-filled {
      display: block
    }

    .heart-container .checkbox:checked~.svg-container .svg-celebrate {
      display: block
    }

    @keyframes keyframes-svg-filled {
      0% {
        transform: scale(0);
      }

      25% {
        transform: scale(1.2);
      }

      50% {
        transform: scale(1);
        filter: brightness(1.5);
      }
    }

    @keyframes keyframes-svg-celebrate {
      0% {
        transform: scale(0);
      }

      50% {
        opacity: 1;
        filter: brightness(1.5);
      }

      100% {
        transform: scale(1.4);
        opacity: 0;
        display: none;
      }
    }
    
    .rating-circle {
      width: 3rem;
      height: 3rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.3);
      border: 2px solid;
    }

    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      z-index: 100;
      background: transparent;
      backdrop-filter: blur(5px);
    }
    
    .logo-container {
      display: flex;
      align-items: center;
    }
    
    .logo-container img {
      height: 36px;
      width: auto;
      margin-right: 10px;
    }
    
    .site-name {
      font-weight: 700;
      font-size: 1.25rem;
      background: linear-gradient(135deg, #a855f7, #6366f1);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .navbar a {
      color: #a855f7;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .navbar a:hover {
      color: #d8b4fe;
      transform: translateY(-2px);
    }
  </style>
</head>
<body class="text-white px-4 py-12 flex flex-col items-center justify-center">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
  
  <div class="navbar">
    <div class="logo-container">
      <img src="assets/images/logo.png" alt="Game Curator Logo">
      <span class="site-name">Game Curator</span>
    </div>
    <div>
      <a href="index.php">Home</a>
      <span class="mx-4">|</span>
      <a href="dashboard.php">Dashboard</a>
      <span class="mx-4">|</span>
      <a href="recommender.php">Recommender</a>
    </div>
  </div>
  
  <div class="glass p-8 max-w-6xl w-full mt-20">
    <h1 class="text-4xl font-bold text-center mb-6 tracking-wide">🎮 My Favorite Games</h1>
    <p class="text-center text-gray-300 mb-8">Games you've favorited by clicking on the heart icon.</p>

    <div class="mt-10">
      <?php if (!empty($favorites)): ?>
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($favorites as $favorite): ?>
            <div class="game-card p-4 bg-gray-800 rounded-lg shadow-lg" data-game-id="<?php echo htmlspecialchars($favorite['game_id']); ?>">
              <div class="relative">
                <?php if (isset($favorite['cover_url']) && !empty($favorite['cover_url'])): ?>
                  <img src="<?php echo $favorite['cover_url']; ?>" alt="<?php echo $favorite['game_name']; ?>" class="rounded-lg object-cover w-full h-56">
                <?php else: ?>
                  <div class="rounded-lg bg-gray-700 w-full h-56 flex items-center justify-center">
                    <span class="text-gray-500">No Image Available</span>
                  </div>
                <?php endif; ?>
                <div class="absolute top-2 right-2 rating-circle border-purple-500">
                  <?php if (!empty($favorite['rating'])): ?>
                    <span><?php echo number_format((float)$favorite['rating'], 1); ?></span>
                  <?php else: ?>
                    <span>N/A</span>
                  <?php endif; ?>
                </div>
                <div class="absolute top-2 left-2">
                  <div class="heart-container" title="Remove from Favorites">
                    <input type="checkbox" class="checkbox favorite-checkbox" checked data-game-id="<?php echo htmlspecialchars($favorite['game_id']); ?>" data-name="<?php echo $favorite['game_name']; ?>">
                    <div class="svg-container">
                      <svg viewBox="0 0 24 24" class="svg-outline" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z">
                        </path>
                      </svg>
                      <svg viewBox="0 0 24 24" class="svg-filled" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Z">
                        </path>
                      </svg>
                      <svg class="svg-celebrate" width="100" height="100" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="10,10 20,20"></polygon>
                        <polygon points="10,50 20,50"></polygon>
                        <polygon points="20,80 30,70"></polygon>
                        <polygon points="90,10 80,20"></polygon>
                        <polygon points="90,50 80,50"></polygon>
                        <polygon points="80,80 70,70"></polygon>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
              <h3 class="text-xl font-bold mt-3 mb-2"><?php echo $favorite['game_name']; ?></h3>
              <p class="text-gray-300 text-sm mb-3 line-clamp-3"><?php echo "No description available."; ?></p>
              <?php if (isset($favorite['first_release_date']) && !empty($favorite['first_release_date'])): ?>
                <p class="text-gray-400 text-sm">Released: <?php echo date("M d, Y", (int)$favorite['first_release_date']); ?></p>
              <?php else: ?>
                <p class="text-gray-400 text-sm">Release date unavailable</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="flex flex-col items-center justify-center py-12">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <h3 class="text-xl font-semibold mb-2">No Favorites Yet</h3>
          <p class="text-gray-300 mb-6">Start adding games to your favorites by clicking on the heart icon!</p>
          <a href="recommender.php" class="bg-purple-600 hover:bg-purple-700 transition px-6 py-3 rounded text-white font-semibold">
            Find Games to Favorite
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="assets/js/favorites.js"></script>
</body>
</html>