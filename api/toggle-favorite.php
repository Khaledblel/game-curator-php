<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php'; // Include database connection

// Check CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) { // Also check for user_id
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id']; // Get user ID from session

// Get data from request body
$data = json_decode(file_get_contents('php://input'), true);

$game_id = $data['game_id'] ?? null;
$name = $data['name'] ?? null;
// Get additional details sent from recommender.js
$cover_url = $data['cover_url'] ?? null; // Ensure this is treated as a string
$summary = $data['summary'] ?? null;
$rating = $data['rating'] ?? null;
$first_release_date = $data['first_release_date'] ?? null; // This might be a timestamp

if ($game_id === null || $name === null) {
    echo json_encode(['success' => false, 'error' => 'Missing game ID or name']);
    exit;
}

// Database interaction
$conn = get_db_connection();
$added = false;

try {
    // Check if the favorite already exists
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("ii", $user_id, $game_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Favorite exists, remove it
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND game_id = ?");
        $stmt->bind_param("ii", $user_id, $game_id);
        if (!$stmt->execute()) {
            throw new Exception("Error removing favorite: " . $stmt->error);
        }
        $added = false;
    } else {
        // Favorite does not exist, add it
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, game_id, game_name, cover_url, rating, first_release_date, added_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisssi", $user_id, $game_id, $name, $cover_url, $rating, $first_release_date);
        if (!$stmt->execute()) {
            throw new Exception("Error adding favorite: " . $stmt->error);
        }
        $added = true;
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'added' => $added]);

} catch (Exception $e) {
    // Log the error instead of echoing sensitive info
    error_log("Favorite toggle error: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { // Check if connection is still alive before closing
       $conn->close();
    }
    echo json_encode(['success' => false, 'error' => 'Database operation failed.']);
}

?>
