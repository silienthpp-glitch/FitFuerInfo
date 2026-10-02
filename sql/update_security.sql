USE fitfuerinfo_db;

CREATE TABLE IF NOT EXISTS login_attempts (
    bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    INDEX idx_login_attempts_expiry (expires_at)
) ENGINE=InnoDB;
