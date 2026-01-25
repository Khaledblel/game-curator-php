<?php
session_start();


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['message'] = "You must log in to access this page.";
    $_SESSION['message_type'] = "error";
    header("Location: auth/login.php");
    exit;
}


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token']; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI Game Recommender - Game Curator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/recommender.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap');

    body {
      font-family: 'Orbitron', sans-serif;
      background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
      min-height: 100vh;
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
    
    .glass {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 1rem;
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .loader {
      width: 44.8px;
      height: 44.8px;
      color: #a855f7;
      position: relative;
      background: radial-gradient(11.2px, currentColor 94%, #0000);
    }

    .loader:before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: 50%;
      background: radial-gradient(10.08px at bottom right, #0000 94%, currentColor) top left,
              radial-gradient(10.08px at bottom left, #0000 94%, currentColor) top right,
              radial-gradient(10.08px at top right, #0000 94%, currentColor) bottom left,
              radial-gradient(10.08px at top left, #0000 94%, currentColor) bottom right;
      background-size: 22.4px 22.4px;
      background-repeat: no-repeat;
      animation: loader 1.5s infinite cubic-bezier(0.3, 1, 0, 1);
    }

    @keyframes loader {
      33% {
        inset: -11.2px;
        transform: rotate(0deg);
      }
      66% {
        inset: -11.2px;
        transform: rotate(90deg);
      }
      100% {
        inset: 0;
        transform: rotate(90deg);
      }
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
    .navbar a { color: #a855f7; text-decoration: none; font-weight: 500; transition: all 0.3s; }
    .navbar a:hover { color: #d8b4fe; transform: translateY(-2px); }
  </style>
</head>
<body class="text-white px-4 py-12 flex flex-col items-center justify-center">
  
  <!-- Navigation Bar -->
  <div class="navbar">
    <div class="logo-container">
      <img src="assets/images/logo.png" alt="Game Curator Logo">
      <span class="site-name">Game Curator</span>
    </div>
    <div>
      <a href="dashboard.php">Dashboard</a>
      <span class="mx-4">|</span>
      <a href="favorites.php">My Favorites</a>
    </div>
  </div>
  
  <div class="glass p-8 max-w-6xl w-full mt-20">
    <h1 class="text-4xl font-bold text-center mb-6 tracking-wide">🎮 AI Game Recommender</h1>
    <p class="text-center text-gray-300 mb-8">Describe the type of game you want, and let AI work its magic.</p>

    <form id="gameForm" class="flex flex-col gap-4 mb-8">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <input
        type="text"
        name="prompt"
        id="prompt"
        placeholder="e.g., An open-world RPG with dragons and deep lore..."
        class="p-4 rounded bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
      />
      <button
        type="submit"
        id="submitButton"
        class="bg-purple-600 hover:bg-purple-700 transition px-6 py-3 rounded text-white font-semibold text-lg"
      >
        Get Recommendations
      </button>
    </form>
    
    <!-- Loading spinner -->
    <div id="loadingSpinner" class="hidden flex justify-center my-8">
      <div class="loader"></div>
    </div>

    <!-- Error message -->
    <div id="errorMessage" class="hidden bg-red-800 text-white p-4 rounded my-8">
      Something went wrong. Please try again.
    </div>

    <div id="results" class="mt-10 hidden">
      <h2 class="text-2xl font-semibold mb-6">Your Game Recommendations</h2>
      
      <!-- Main Game Recommendation (Top Pick) -->
      <div id="mainGameCard" class="mb-10"></div>
      
      <!-- Similar Games Section -->
      <h3 class="text-xl font-semibold mb-4">Similar Games You Might Enjoy</h3>
      <div id="similarGamesContainer" class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3"></div>
    </div>
  </div>

  <!-- Franchise Timeline Modal -->
  <div id="franchiseModal" class="modal-backdrop">
    <div class="modal-content">
      <button id="closeModal" class="modal-close" aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      
      <div class="franchise-timeline">
        <h3 class="text-2xl font-semibold mb-6 flex items-center gap-2">
          <span id="franchiseName">Franchise</span> Timeline
        </h3>
        <div id="timelineContainer" class="timeline"></div>
      </div>
    </div>
  </div>

  <script src="assets/js/recommender.js"></script>
</body>
</html>