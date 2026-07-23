-- Magulsakwala-tv database schema
-- Matches the design documented in PROJECT_SCOPE.md

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL DEFAULT 'youtube',
    channel_id VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NULL,
    api_key VARCHAR(500) NULL,
    api_secret VARCHAR(500) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS thumbnails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL UNIQUE,
    background_image VARCHAR(500) NULL,
    image_1 VARCHAR(500) NULL,
    image_2 VARCHAR(500) NULL,
    image_3 VARCHAR(500) NULL,
    image_4 VARCHAR(500) NULL,
    image_5 VARCHAR(500) NULL,
    text_1 VARCHAR(255) NULL,
    text_2 VARCHAR(255) NULL,
    text_3 VARCHAR(255) NULL,
    rendered_image VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    video_path VARCHAR(500) NULL,
    topic VARCHAR(500) NULL,
    content TEXT NULL,
    voice_enabled TINYINT(1) NOT NULL DEFAULT 0,
    voice_over_path VARCHAR(500) NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    tags TEXT NULL,
    thumbnail_ref INT NULL,
    video_type ENUM('long', 'short') NULL,
    status ENUM('draft', 'prepared', 'scheduled', 'published', 'failed') NOT NULL DEFAULT 'draft',
    youtube_video_id VARCHAR(255) NULL,
    scheduled_at DATETIME NULL,
    published_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_video_operations_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_video_operations_thumbnail FOREIGN KEY (thumbnail_ref) REFERENCES thumbnails(id) ON DELETE SET NULL
);

ALTER TABLE thumbnails
    ADD CONSTRAINT fk_thumbnails_video FOREIGN KEY (video_id) REFERENCES video_operations(id) ON DELETE CASCADE;
