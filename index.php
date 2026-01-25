<?php

require_once 'config/db_init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Game Curator - AI Game Recommender</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/common.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <script src="assets/js/smooth_scroll.js"></script>
  <script src="assets/js/floating_stickers.js"></script>
</head>
<body class="text-white">

  <!-- Hero Section -->
  <section class="min-h-screen flex flex-col justify-center items-center text-center px-6 py-20 relative">
   
    <div id="hero-sticker-container" class="sticker-container"></div>
    
    <!-- Logo and Site Name at the top -->
    <div class="absolute top-4 left-4 logo-container">
      <img src="assets/images/logo.png" alt="Game Curator Logo">
      <span class="site-name">Game Curator</span>
    </div>
   
    <div class="section-content flex flex-col justify-center items-center text-center w-full">
      <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
        🎮 Discover Your Next Favorite Game
      </h1>
      <p class="text-xl text-gray-300 max-w-2xl mb-10">
        Describe the kind of game you're craving and let our AI find the perfect match using real-time data from IGDB.
      </p>
      <a href="#start" class="bg-purple-600 hover:bg-purple-700 transition px-8 py-4 rounded-full text-lg font-semibold scroll-btn">
        Get Started
      </a>
    </div>

    <!-- User Authentication Links -->
    <div class="absolute top-4 right-4 flex space-x-4">
      <a href="auth/login.php" class="text-white hover:text-purple-300">Login</a>
      <span class="text-gray-500">|</span>
      <a href="auth/register.php" class="text-white hover:text-purple-300">Register</a>
    </div>
  </section>

  <!-- Features Section -->
  <section class="bg-gray-900 py-20 px-6" id="start">
    <div class="max-w-5xl mx-auto">
      <h2 class="text-4xl font-bold text-center mb-12">What Makes It Awesome?</h2>
      <div class="grid md:grid-cols-3 gap-10">
        <div class="nitro-card">
          <div class="nitro-card-inner p-6">
            <h3 class="text-2xl font-semibold mb-4 card-header">💡 Prompt-Based AI</h3>
            <p class="text-gray-300">Just type what you feel like playing, and we'll translate your prompt into data-driven recommendations.</p>
          </div>
        </div>
        <div class="nitro-card">
          <div class="nitro-card-inner p-6">
            <h3 class="text-2xl font-semibold mb-4 card-header">🧠 Gemini + IGDB</h3>
            <p class="text-gray-300">We use cutting-edge AI to understand your mood and match it with detailed game data from IGDB.</p>
          </div>
        </div>
        <div class="nitro-card">
          <div class="nitro-card-inner p-6">
            <h3 class="text-2xl font-semibold mb-4 card-header">🚀 Fast & Beautiful</h3>
            <p class="text-gray-300">Smooth interface, fast recommendations, and beautiful visuals. No fluff, just games.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Call to Action -->
  <section class="py-20 px-6 text-center bg-gradient-to-r from-purple-800 via-indigo-800 to-blue-800 relative">
    <div id="cta-sticker-container" class="sticker-container"></div>
    <div class="section-content flex flex-col justify-center items-center text-center w-full">
      <h2 class="text-4xl font-bold mb-4">Ready to Find Your Game?</h2>
      <p class="text-gray-300 mb-8 text-lg">Start your search with just one sentence.</p>
      <a href="auth/login.php" class="bg-white text-black px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-200">
        Login to Start
      </a>
      <p class="text-gray-300 mt-3">
        Don't have an account? <a href="auth/register.php" class="text-purple-300 hover:text-purple-200 underline">Register now</a>
      </p>
    </div>
  </section>

  <!-- Footer -->
  <footer class="py-10 text-center text-gray-400 bg-gray-950">
    &copy; 2025 AI Game Recommender — Built for gamers by gamers 🎮
  </footer>
</body>
</html>