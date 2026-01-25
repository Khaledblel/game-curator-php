<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Game Curator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../assets/css/common.css">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="text-white min-h-screen flex items-center justify-center">
  <div class="w-full max-w-md p-8 bg-gray-800 bg-opacity-80 rounded-lg shadow-xl">
    <!-- Logo and site name -->
    <div class="logo-container">
      <img src="../assets/images/logo.png" alt="Game Curator Logo">
      <span class="site-name">Game Curator</span>
    </div>
  
    <h1 class="text-3xl font-bold text-center mb-6">Login</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
      <div class="mb-4 p-3 <?php echo $_SESSION['message_type'] == 'error' ? 'bg-red-600' : 'bg-green-600'; ?> bg-opacity-70 rounded">
        <?php echo $_SESSION['message']; ?>
      </div>
      <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <form method="post" action="login_process.php" class="space-y-6">
      <div>
        <label for="username" class="block text-sm font-medium">Username</label>
        <div class="mt-1">
          <input type="text" name="username" id="username" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
      </div>
      
      <div>
        <label for="password" class="block text-sm font-medium">Password</label>
        <div class="mt-1">
          <input type="password" name="password" id="password" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
      </div>
      
      <div>
        <button type="submit" class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 rounded-lg font-medium">
          Login
        </button>
      </div>
    </form>
    
    <div class="mt-6 text-center">
      <p>Don't have an account? <a href="register.php" class="text-purple-400 hover:text-purple-300">Register here</a></p>
      <p class="mt-4">
        <a href="../index.php" class="text-gray-400 hover:text-white">← Back to Home</a>
      </p>
    </div>
  </div>
</body>
</html>