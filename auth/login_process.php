<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['message'] = "Both username and password are required.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit;
    }
    
    try {
        $conn = get_db_connection();
        
        $stmt = $conn->prepare("SELECT id, username, password, profile_image FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (is_array($user) && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['profile_image'] = $user['profile_image'];
                $_SESSION['logged_in'] = true;
                
                header("Location: ../dashboard.php");
                exit;
            } else {
                $_SESSION['message'] = "Invalid username or password.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['message_type'] = "error";
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Login error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: login.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>