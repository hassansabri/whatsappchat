-- Chat application schema for `chating` database
USE chating;

CREATE TABLE IF NOT EXISTS genders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO genders (name, sort_order) VALUES
('Male', 1),
('Female', 2),
('Other', 3),
('Prefer not to say', 4)
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  country_code VARCHAR(8) NOT NULL DEFAULT '',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  gender_id INT UNSIGNED NOT NULL,
  password VARCHAR(255) NOT NULL,
  display_name VARCHAR(80) NOT NULL,
  avatar_color VARCHAR(7) NOT NULL DEFAULT '#0d6efd',
  avatar_path VARCHAR(255) NULL,
  user_type ENUM('system','normal') NOT NULL DEFAULT 'normal',
  last_seen DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_phone_pair (country_code, phone),
  CONSTRAINT fk_users_gender FOREIGN KEY (gender_id) REFERENCES genders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_one INT UNSIGNED NOT NULL,
  user_two INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pair (user_one, user_two),
  CONSTRAINT fk_conv_user_one FOREIGN KEY (user_one) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_user_two FOREIGN KEY (user_two) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_conversation_created (conversation_id, created_at),
  INDEX idx_conversation_unread (conversation_id, is_read, sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo users (password for all: password123). New users default to normal.
INSERT INTO users (username, email, country_code, phone, gender_id, password, display_name, avatar_color, user_type) VALUES
('alice', 'alice@example.com', '+92', '3001111111', 2, '$2y$12$y59rcoLnKv726wxnovyCneiCNPTokEiUtSRJAfbF5xo/f5GZg7Pem', 'Alice Johnson', '#0d6efd', 'system'),
('bob', 'bob@example.com', '+92', '3002222222', 1, '$2y$12$y59rcoLnKv726wxnovyCneiCNPTokEiUtSRJAfbF5xo/f5GZg7Pem', 'Bob Smith', '#198754', 'normal'),
('carol', 'carol@example.com', '+92', '3003333333', 2, '$2y$12$y59rcoLnKv726wxnovyCneiCNPTokEiUtSRJAfbF5xo/f5GZg7Pem', 'Carol Lee', '#dc3545', 'normal')
ON DUPLICATE KEY UPDATE username = VALUES(username), user_type = VALUES(user_type);

ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type ENUM('system','normal') NOT NULL DEFAULT 'normal' AFTER avatar_color;
ALTER TABLE users ADD COLUMN IF NOT EXISTS country_code VARCHAR(8) NOT NULL DEFAULT '' AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NOT NULL DEFAULT '' AFTER country_code;
ALTER TABLE users ADD COLUMN IF NOT EXISTS gender_id INT UNSIGNED NULL AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL AFTER avatar_color;

CREATE TABLE IF NOT EXISTS typing_status (
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('idle','typing','erasing') NOT NULL DEFAULT 'idle',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (conversation_id, user_id),
  CONSTRAINT fk_typing_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE messages
  ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) NULL AFTER body,
  ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) NULL AFTER file_name,
  ADD COLUMN IF NOT EXISTS file_type VARCHAR(120) NULL AFTER file_path,
  ADD COLUMN IF NOT EXISTS file_size INT UNSIGNED NULL AFTER file_type,
  ADD COLUMN IF NOT EXISTS duration_seconds DECIMAL(8,1) NULL AFTER file_size;

ALTER TABLE messages ADD COLUMN location_lat DECIMAL(10,7) NULL, ADD COLUMN location_lng DECIMAL(10,7) NULL;

ALTER TABLE messages
  ADD COLUMN IF NOT EXISTS is_delivered TINYINT(1) NOT NULL DEFAULT 0 AFTER is_read,
  ADD COLUMN IF NOT EXISTS is_played TINYINT(1) NOT NULL DEFAULT 0 AFTER is_delivered;

ALTER TABLE messages
  ADD COLUMN IF NOT EXISTS deleted_everyone TINYINT(1) NOT NULL DEFAULT 0 AFTER is_played,
  ADD COLUMN IF NOT EXISTS deleted_everyone_at DATETIME NULL AFTER deleted_everyone;

CREATE TABLE IF NOT EXISTS message_hides (
  message_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id, user_id),
  KEY idx_hide_user (user_id),
  CONSTRAINT fk_hide_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  CONSTRAINT fk_hide_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_user_state (
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  cleared_before_id INT UNSIGNED NOT NULL DEFAULT 0,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (conversation_id, user_id),
  KEY idx_cus_user (user_id),
  CONSTRAINT fk_cus_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_cus_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS friendships (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_one INT UNSIGNED NOT NULL,
  user_two INT UNSIGNED NOT NULL,
  requested_by INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pair (user_one, user_two)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
