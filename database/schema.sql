-- Users table schema for Game Curator
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    age INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    last_login DATETIME NULL
);

-- Create index on username and email for faster lookups
CREATE INDEX idx_username ON users (username);
CREATE INDEX idx_email ON users (email);

-- Add comments to the table
ALTER TABLE users
COMMENT = 'Stores user registration information for Game Curator application';

-- Favorites table schema
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL, -- Assuming game_id is an integer from the external API
    game_name VARCHAR(255) NOT NULL, -- Store name for easier display
    cover_url VARCHAR(255) NULL, -- URL for the game's cover image
    rating DECIMAL(4, 1) NULL, -- Game rating (e.g., 85.5)
    first_release_date INT NULL, -- Release date as Unix timestamp
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY user_game_unique (user_id, game_id) -- Prevent duplicate favorites
);

-- Create index for faster lookups
CREATE INDEX idx_user_id ON favorites (user_id);
CREATE INDEX idx_game_id ON favorites (game_id);

-- Add comments to the table
ALTER TABLE favorites
COMMENT = 'Stores user favorite games';