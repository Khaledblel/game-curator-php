<?php

require_once 'database.php';

function initialize_database() {
    $conn = get_db_connection();
    

    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result) {
        $table_exists = ($result->num_rows > 0);
    }
    

    if (!$table_exists) {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(30) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            age INT NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            last_login DATETIME NULL
        )";
        
        if (!$conn->query($sql)) {
            error_log("Error creating users table: " . $conn->error);

        } else {
          
            $conn->query("CREATE INDEX idx_username ON users (username)");
            $conn->query("CREATE INDEX idx_email ON users (email)");
            
           
            error_log("Successfully created users table in gcdb database");
        }
    }
    
   
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'favorites'");
    if ($result) {
        $table_exists = ($result->num_rows > 0);
    }
    
  
    if (!$table_exists) {
        $sql = "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            game_id INT NOT NULL, 
            game_name VARCHAR(255) NOT NULL, 
            cover_url VARCHAR(255) NULL, 
            rating DECIMAL(4, 1) NULL,
            first_release_date INT NULL, 
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY user_game_unique (user_id, game_id) 
        )";
        
        if (!$conn->query($sql)) {
            error_log("Error creating favorites table: " . $conn->error);
        } else {
            // Create indexes
            $conn->query("CREATE INDEX idx_user_id ON favorites (user_id)");
            $conn->query("CREATE INDEX idx_game_id ON favorites (game_id)");
            
            // Log success
            error_log("Successfully created favorites table in gcdb database");
        }
    }
    
    $conn->close();
}


initialize_database();
?>