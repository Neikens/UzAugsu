-- Create database
CREATE DATABASE IF NOT EXISTS uzaugsu_lv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uzaugsu_lv;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(100) NOT NULL,
    animal_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_nickname (nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pullups entries
CREATE TABLE IF NOT EXISTS pullups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    count INT NOT NULL,
    entry_date DATE NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, entry_date),
    INDEX idx_entry_date (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bible verses entries
CREATE TABLE IF NOT EXISTS verses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    verse_reference VARCHAR(150) NOT NULL,
    verse_text TEXT,
    entry_date DATE NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_first_of_day BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, entry_date),
    INDEX idx_entry_date (entry_date),
    INDEX idx_first_of_day (is_first_of_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bible reference database (for autocomplete)
CREATE TABLE IF NOT EXISTS bible_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_lv VARCHAR(100) NOT NULL,
    book_en VARCHAR(100),
    chapter INT NOT NULL,
    verse INT NOT NULL,
    full_reference VARCHAR(150) NOT NULL,
    text_lv TEXT,
    INDEX idx_reference (full_reference),
    INDEX idx_book (book_lv),
    FULLTEXT idx_search (book_lv, full_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popular verses (for random suggestions)
CREATE TABLE IF NOT EXISTS popular_verses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(150) NOT NULL,
    text_lv TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (optional, for better session management)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;