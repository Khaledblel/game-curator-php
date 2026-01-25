<?php
session_start();
require_once '../config/database.php';
require_once '../config/db_init.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
    $password1 = $_POST['password1'];
    $password2 = $_POST['password2'];
    
    if (empty($username)) {
        $errors['username_error'] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors['username_error'] = 'Username must be between 3 and 30 characters';
    }
    
    if (empty($email)) {
        $errors['email_error'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email_error'] = 'Please enter a valid email address';
    }
    
    if (empty($age)) {
        $errors['age_error'] = 'Age is required';
    } elseif ($age < 13) {
        $errors['age_error'] = 'You must be at least 13 years old to register';
    }
    
    if (empty($password1)) {
        $errors['password1_error'] = 'Password is required';
    } elseif (strlen($password1) < 8) {
        $errors['password1_error'] = 'Password must be at least 8 characters';
    }
    
    if ($password1 != $password2) {
        $errors['password2_error'] = 'Passwords do not match';
    }
    
    $profile_image_path = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024;
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors['profile_image_error'] = 'Only JPEG, PNG, and GIF images are allowed';
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors['profile_image_error'] = 'Image size must be less than 2MB';
        } elseif ($_FILES['profile_image']['error'] != 0) {
            $errors['profile_image_error'] = 'Error uploading file';
        } else {
            $upload_dir = '../uploads/profile_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = $username . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image_path = 'uploads/profile_images/' . $filename;
            } else {
                $errors['profile_image_error'] = 'Failed to save the uploaded image';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $conn = get_db_connection();
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $_SESSION['message'] = 'Username already exists';
                $_SESSION['message_type'] = 'error';
                header('Location: register.php');
                exit();
            }
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $_SESSION['message'] = 'Email already registered';
                $_SESSION['message_type'] = 'error';
                header('Location: register.php');
                exit();
            }
            
            $password_hash = password_hash($password1, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, age, password, profile_image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssiss", $username, $email, $age, $password_hash, $profile_image_path);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Registration successful! You can now log in.';
                $_SESSION['message_type'] = 'success';
                header('Location: login.php');
                exit();
            } else {
                throw new Exception('Registration failed: ' . $conn->error);
            }
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: register.php');
            exit();
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
            if (isset($conn)) {
                $conn->close();
            }
        }
    } else {
        foreach ($errors as $key => $value) {
            $_SESSION[$key] = $value;
        }
        $_SESSION['message'] = 'Please fix the errors below';
        $_SESSION['message_type'] = 'error';
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>