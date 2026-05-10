SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS sporttime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sporttime;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    game_level TINYINT NOT NULL DEFAULT 0 COMMENT '0=Unset, 1=Beginner, 2=Beginner-Mid, 3=Middle, 4=Strong, 5=Super-Strong',
    role TINYINT NOT NULL DEFAULT 1 COMMENT '1=User, 2=Advanced, 3=Organizer, 4=Admin',
    vk_id VARCHAR(100),
    notify_settings VARCHAR(20) DEFAULT '1-1-1-1-1',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 2=Blocked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255),
    sport_type VARCHAR(100),
    level VARCHAR(100) DEFAULT 'Средний',
    place VARCHAR(255),
    event_date DATE NOT NULL,
    event_time TIME,
    max_reg_date DATE,
    max_members INT DEFAULT 0,
    description TEXT,
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1=Planned, 2=Registration, 3=Completed',
    adv_payment TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_id INT NOT NULL,
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    invited_by INT NULL,
    is_reserve TINYINT NOT NULL DEFAULT 0 COMMENT '0=Main, 1=Reserve',
    payment_confirmed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT
) ENGINE=InnoDB;

-- Seed config
INSERT INTO config (name, value) VALUES
    ('site_title', 'Sport Time - Запись на мероприятия'),
    ('bg_color', '#1a1a2e'),
    ('vk_public_id', ''),
    ('vk_token', '');
