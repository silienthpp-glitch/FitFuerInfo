-- Nachtrag für bereits vorhandene Datenbanken
USE fitfuerinfo_db;

ALTER TABLE users
    MODIFY password_hash VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS password_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_tokens_hash (token_hash),
    INDEX idx_password_tokens_user (user_id),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
