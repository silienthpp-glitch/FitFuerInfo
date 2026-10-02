CREATE DATABASE IF NOT EXISTS fitfuerinfo_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE fitfuerinfo_db;


-- Mitarbeiter / Benutzer
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('employee', 'admin') NOT NULL DEFAULT 'employee',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_active (active)
) ENGINE=InnoDB;


-- Einmal-Aktivierungs- und Reset-Codes (nur Hash, nie Klartext)
CREATE TABLE password_tokens (
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


-- Kursprofile
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    max_participants INT NOT NULL,
    created_by INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_courses_created_by (created_by),
    INDEX idx_courses_active (active),

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)
) ENGINE=InnoDB;


-- Eigentümer eines Kurses
CREATE TABLE course_owners (
    course_id INT NOT NULL,
    user_id INT NOT NULL,

    PRIMARY KEY (course_id, user_id),

    FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- Verfügbare Software
CREATE TABLE software (
    software_id INT AUTO_INCREMENT PRIMARY KEY,
    software_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;


-- Benötigte Software eines Kurses
CREATE TABLE course_software (
    course_id INT NOT NULL,
    software_id INT NOT NULL,

    PRIMARY KEY (course_id, software_id),

    FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE,

    FOREIGN KEY (software_id)
        REFERENCES software(software_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- Räume
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(50) NOT NULL UNIQUE,
    computer_count INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Installierte Software eines Raumes
CREATE TABLE room_software (
    room_id INT NOT NULL,
    software_id INT NOT NULL,

    PRIMARY KEY (room_id, software_id),

    FOREIGN KEY (room_id)
        REFERENCES rooms(room_id)
        ON DELETE CASCADE,

    FOREIGN KEY (software_id)
        REFERENCES software(software_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- Mitarbeiter, die einen Raum bearbeiten dürfen
CREATE TABLE room_editors (
    room_id INT NOT NULL,
    user_id INT NOT NULL,

    PRIMARY KEY (room_id, user_id),

    FOREIGN KEY (room_id)
        REFERENCES rooms(room_id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- Raumbuchungen
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    course_id INT NOT NULL,
    created_by INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_bookings_room_date (room_id, booking_date),
    INDEX idx_bookings_course (course_id),
    INDEX idx_bookings_created_by (created_by),
    INDEX idx_bookings_date (booking_date),

    FOREIGN KEY (room_id)
        REFERENCES rooms(room_id),

    FOREIGN KEY (course_id)
        REFERENCES courses(course_id),

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Hinweis: bookings.course_id hat kein ON DELETE CASCADE.
-- Vergangene Buchungen bleiben erhalten. Kurse mit Historie werden
-- deshalb deaktiviert statt physisch gelöscht.


-- Beispielsoftware für Tests und Zuordnungen
INSERT INTO software (software_name) VALUES
('Wireshark'),
('XAMPP'),
('MySQL Workbench'),
('Visual Studio Code'),
('Cisco Packet Tracer'),
('LibreOffice');
