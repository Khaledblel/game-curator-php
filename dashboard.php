<?php
session_start();


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['message'] = "You must log in to access this page.";
    $_SESSION['message_type'] = "error";
    header("Location: auth/login.php");
    exit;
}


$username = $_SESSION['username'];
$profile_image = $_SESSION['profile_image'] ? "../{$_SESSION['profile_image']}" : "assets/images/default-profile.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Game Curator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/common.css">
    <style>
        /* Fix for footer positioning */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
        }
        
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header/Navigation -->
    <header class="bg-gray-800 shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <img src="assets/images/logo.png" alt="Game Curator Logo" class="h-10 mr-3">
                <h1 class="text-xl font-bold">Game Curator</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-8 w-8 rounded-full object-cover mr-2">
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Welcome to your Dashboard, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="mb-4">You've successfully logged into the Game Curator platform.</p>
            
            <div class="bg-purple-900 bg-opacity-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">What's Next?</h3>
                <p>Here you'll be able to:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>Browse your game collection</li>
                    <li>Add new games to your library</li>
                    <li>Get personalized game recommendations</li>
                </ul>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold mb-3">My Collection</h3>
                <p class="text-gray-400">Start building your game library.</p>
                <a href="favorites.php" class="mt-4 bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-white inline-block">View Collection</a>
            </div>
            
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold mb-3">Discover Games</h3>
                <p class="text-gray-400">Find new games based on your preferences.</p>
                <a href="recommender.php" class="mt-4 bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-white inline-block">Explore</a>
            </div>
            
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold mb-3">My Profile</h3>
                <p class="text-gray-400">Edit your profile and preferences.</p>
                <button id="openProfileModal" class="mt-4 bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-white">Edit Profile</button>
            </div>
        </div>
    </main>
    
    <!-- Profile Edit Modal -->
    <div id="profileModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="bg-black bg-opacity-50 absolute inset-0"></div>
        <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md relative z-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Edit Profile</h3>
                <button id="closeProfileModal" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="profileForm" action="update_profile.php" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium mb-1">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" 
                           class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" id="email" name="email" 
                           class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
                </div>
                
                <div class="mb-6">
                    <label for="new_profile_image" class="block text-sm font-medium mb-1">Profile Image</label>
                    <div class="flex items-center space-x-4 mb-2">
                        <img src="<?php echo $profile_image; ?>" alt="Current profile" class="h-16 w-16 rounded-full object-cover">
                        <span class="text-sm text-gray-400">Current image</span>
                    </div>
                    <input type="file" id="new_profile_image" name="new_profile_image" accept="image/jpeg,image/png,image/gif"
                           class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white">
                    <p class="text-xs text-gray-400 mt-1">Optional. Max size: 2MB (JPEG, PNG, GIF)</p>
                </div>
                
                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-medium mb-1">Current Password</label>
                    <input type="password" id="current_password" name="current_password" 
                           class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white"
                           placeholder="Required to confirm changes">
                </div>
                
                <div class="mb-4">
                    <label for="new_password" class="block text-sm font-medium mb-1">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="w-full py-2 px-3 border border-gray-700 bg-gray-900 rounded-md text-white"
                           placeholder="Leave blank to keep current password">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelProfileEdit" 
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <footer class="bg-gray-800 py-6">
        <div class="container mx-auto px-4 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> Game Curator. All rights reserved.</p>
        </div>
    </footer>

    <!-- Add profile edit modal script -->
    <script src="assets/js/profile_modal.js"></script>
</body>
</html>