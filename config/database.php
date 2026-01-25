<?php

$db_config = [
    'host' => 'YOUR_DB_HOST_HERE', 
    'username' => 'YOUR_DB_USERNAME_HERE', 
    'password' => 'YOUR_DB_PASSWORD_HERE', 
    'database' => 'YOUR_DB_NAME_HERE', 
    'port' => 3306 
];


function get_db_connection() {
    global $db_config;
    
    $conn = mysqli_init();
    
    
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
    
    // Connect with SSL
    $result = mysqli_real_connect(
        $conn, 
        $db_config['host'], 
        $db_config['username'], 
        $db_config['password'], 
        $db_config['database'], 
        $db_config['port'],
        NULL,
        MYSQLI_CLIENT_SSL
    );
    
    
    if (!$result) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    
    mysqli_set_charset($conn, "utf8");
    
    return $conn;
}
?>