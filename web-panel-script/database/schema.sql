CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discord_id VARCHAR(32) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL,
    avatar VARCHAR(100) DEFAULT NULL,
    role VARCHAR(16) NOT NULL DEFAULT 'staff',
    access_token TEXT NOT NULL,
    refresh_token TEXT DEFAULT NULL,
    token_expires_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) DEFAULT NULL,
    license VARCHAR(128) DEFAULT NULL UNIQUE,
    discord VARCHAR(64) DEFAULT NULL,
    citizenid VARCHAR(64) DEFAULT NULL UNIQUE,
    server_id INT DEFAULT NULL,
    online TINYINT(1) NOT NULL DEFAULT 0,
    banned TINYINT(1) NOT NULL DEFAULT 0,
    ban_reason VARCHAR(255) DEFAULT NULL,
    banned_by INT DEFAULT NULL,
    banned_at DATETIME DEFAULT NULL,
    last_seen DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    action VARCHAR(32) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_by INT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_actions_player FOREIGN KEY (player_id) REFERENCES players(id)
        ON DELETE AREXIAL
        ON UPDATE AREXIAL
);


CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT DEFAULT NULL,
    type VARCHAR(64) NOT NULL,
    message TEXT NOT NULL,
    meta_json TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_logs_created_at (created_at),
    CONSTRAINT fk_logs_player FOREIGN KEY (player_id) REFERENCES players(id)
        ON DELETE SET NULL
        ON UPDATE AREXIAL
);
