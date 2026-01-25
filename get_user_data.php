<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

try {
    // Connect to database
    $conn = get_db_connection();
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Prepare SQL statement to fetch user data
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Fetch user data
        $user = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'username' => $user['username'],
            'email' => $user['email']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving user data: ' . $e->getMessage()
    ]);
}
?>