-- Database creation for Game Mods Platform
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS game_mods_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE game_mods_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Games table
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    publisher VARCHAR(100),
    release_year YEAR,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon_class VARCHAR(50),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- Mods table
CREATE TABLE IF NOT EXISTS mods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    description TEXT,
    version VARCHAR(20),
    game_id INT NOT NULL,
    category_id INT,
    user_id INT NOT NULL,
    download_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    file_size BIGINT,
    download_url VARCHAR(255),
    screenshot_main VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_game (game_id),
    INDEX idx_category (category_id),
    INDEX idx_user (user_id),
    INDEX idx_featured (is_featured),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- Mod images gallery
CREATE TABLE IF NOT EXISTS mod_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mod_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mod_id) REFERENCES mods(id) ON DELETE CASCADE,
    INDEX idx_mod (mod_id)
) ENGINE=InnoDB;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mod_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mod_id) REFERENCES mods(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_mod (user_id, mod_id),
    INDEX idx_mod (mod_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Downloads tracking
CREATE TABLE IF NOT EXISTS downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mod_id INT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mod_id) REFERENCES mods(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mod (mod_id),
    INDEX idx_downloaded_at (downloaded_at)
) ENGINE=InnoDB;

-- Tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- Mod-Tag relationship
CREATE TABLE IF NOT EXISTS mod_tags (
    mod_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (mod_id, tag_id),
    FOREIGN KEY (mod_id) REFERENCES mods(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_mod (mod_id),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO games (title, slug, description, publisher, release_year, cover_image) VALUES
('The Witcher 3', 'the-witcher-3', 'Action RPG developed by CD Projekt Red', 'CD Projekt', 2015, '/images/games/witcher3.jpg'),
('Skyrim', 'skyrim', 'Open world fantasy RPG by Bethesda', 'Bethesda Game Studios', 2011, '/images/games/skyrim.jpg'),
('Cyberpunk 2077', 'cyberpunk-2077', 'Futuristic action RPG', 'CD Projekt', 2020, '/images/games/cyberpunk.jpg'),
('Minecraft', 'minecraft', 'Sandbox building game', 'Mojang Studios', 2011, '/images/games/minecraft.jpg'),
('GTA V', 'gta-v', 'Open world action-adventure game', 'Rockstar Games', 2013, '/images/games/gtav.jpg');

INSERT INTO categories (name, slug, icon_class, sort_order) VALUES
('Graphics', 'graphics', 'fa-image', 1),
('Gameplay', 'gameplay', 'fa-gamepad', 2),
('Characters', 'characters', 'fa-user', 3),
('Weapons', 'weapons', 'fa-gun', 4),
('Vehicles', 'vehicles', 'fa-car', 5),
('Maps', 'maps', 'fa-map', 6),
('Scripts', 'scripts', 'fa-code', 7),
('Other', 'other', 'fa-box', 8);

INSERT INTO tags (name, slug) VALUES
('HD', 'hd'),
('Realistic', 'realistic'),
('Fantasy', 'fantasy'),
('Sci-Fi', 'sci-fi'),
('Multiplayer', 'multiplayer'),
('Singleplayer', 'singleplayer'),
('Free', 'free'),
('Popular', 'popular');
