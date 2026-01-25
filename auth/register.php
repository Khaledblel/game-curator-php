<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Game Curator</title>
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
  
    <h1 class="text-3xl font-bold text-center mb-6">Register</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
      <div class="mb-4 p-3 <?php echo $_SESSION['message_type'] == 'error' ? 'bg-red-600' : 'bg-green-600'; ?> bg-opacity-70 rounded">
        <?php echo $_SESSION['message']; ?>
      </div>
      <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <form method="post" action="register_process.php" class="space-y-6" enctype="multipart/form-data">
      <div>
        <label for="username" class="block text-sm font-medium">Username</label>
        <div class="mt-1">
          <input type="text" name="username" id="username" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <?php if (isset($_SESSION['username_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['username_error']; ?></p>
          <?php unset($_SESSION['username_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <label for="email" class="block text-sm font-medium">Email</label>
        <div class="mt-1">
          <input type="email" name="email" id="email" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <?php if (isset($_SESSION['email_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['email_error']; ?></p>
          <?php unset($_SESSION['email_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <label for="age" class="block text-sm font-medium">Age</label>
        <div class="mt-1">
          <input type="number" name="age" id="age" min="13" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <?php if (isset($_SESSION['age_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['age_error']; ?></p>
          <?php unset($_SESSION['age_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <label for="profile_image" class="block text-sm font-medium">Profile Image</label>
        <div class="mt-1">
          <input type="file" name="profile_image" id="profile_image" accept="image/jpeg,image/png,image/gif" class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <p class="text-gray-400 text-xs mt-1">Optional. Max size: 2MB (JPEG, PNG, GIF)</p>
        <?php if (isset($_SESSION['profile_image_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['profile_image_error']; ?></p>
          <?php unset($_SESSION['profile_image_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <label for="password1" class="block text-sm font-medium">Password</label>
        <div class="mt-1">
          <input type="password" name="password1" id="password1" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <?php if (isset($_SESSION['password1_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['password1_error']; ?></p>
          <?php unset($_SESSION['password1_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <label for="password2" class="block text-sm font-medium">Confirm Password</label>
        <div class="mt-1">
          <input type="password" name="password2" id="password2" required class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
        </div>
        <?php if (isset($_SESSION['password2_error'])): ?>
          <p class="text-red-400 text-sm mt-1"><?php echo $_SESSION['password2_error']; ?></p>
          <?php unset($_SESSION['password2_error']); ?>
        <?php endif; ?>
      </div>
      
      <div>
        <button type="submit" class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 rounded-lg font-medium">
          Register
        </button>
      </div>
    </form>
    
    <div class="mt-6 text-center">
      <p>Already have an account? <a href="login.php" class="text-purple-400 hover:text-purple-300">Login here</a></p>
      <p class="mt-4">
        <a href="../index.php" class="text-gray-400 hover:text-white">← Back to Home</a>
      </p>
    </div>
  </div>
</body>
</html>