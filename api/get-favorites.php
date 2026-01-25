<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['is_favorite' => false, 'error' => 'User not logged in']);
    exit;
}


$game_id = $_GET['game_id'] ?? null;

if ($game_id === null) {
    echo json_encode(['is_favorite' => false, 'error' => 'Game ID not provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_favorite = false;

try {
    
    $conn = get_db_connection();
    
   
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("is", $user_id, $game_id); 
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
   
    $is_favorite = ($row['count'] > 0);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error checking favorite status: " . $e->getMessage());
    echo json_encode(['is_favorite' => false, 'error' => 'Database error']);
    exit;
}

echo json_encode(['is_favorite' => $is_favorite]);
?>
