<?php
session_start();
require_once 'config/database.php';


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}


$user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';


if (empty($username)) {
    echo json_encode([
        'success' => false,
        'error' => 'Username is required'
    ]);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Valid email is required'
    ]);
    exit;
}

if (empty($current_password)) {
    echo json_encode([
        'success' => false,
        'error' => 'Current password is required to update profile'
    ]);
    exit;
}

try {
    
    $conn = get_db_connection();
    
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Handle username change and check if new username already exists (if changed)
    if ($username !== $current_username) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Username already taken'
            ]);
            exit;
        }
    }
    
    // Handle email checks
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Email already registered to another account'
        ]);
        exit;
    }
    
    // Handle profile image upload if provided
    $profile_image_path = $_SESSION['profile_image']; // Keep current path by default
    
    if (!empty($_FILES['new_profile_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['new_profile_image']['type'], $allowed_types)) {
            echo json_encode([
                'success' => false,
                'error' => 'Only JPEG, PNG, and GIF images are allowed'
            ]);
            exit;
        }
        
        if ($_FILES['new_profile_image']['size'] > $max_size) {
            echo json_encode([
                'success' => false,
                'error' => 'Image size must be less than 2MB'
            ]);
            exit;
        }
        
        if ($_FILES['new_profile_image']['error'] != 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Error uploading file'
            ]);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['new_profile_image']['name'], PATHINFO_EXTENSION);
        $filename = $username . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['new_profile_image']['tmp_name'], $target_file)) {
            // Update profile image path
            $profile_image_path = $target_file;
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to save image'
            ]);
            exit;
        }
    }
    
    // Update user data in the database
    if (!empty($new_password)) {
        // Update with new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $password_hash, $profile_image_path, $user_id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $profile_image_path, $user_id);
    }
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['username'] = $username;
        $_SESSION['profile_image'] = $profile_image_path;
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update profile: ' . $conn->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error updating profile: ' . $e->getMessage()
    ]);
}
?>