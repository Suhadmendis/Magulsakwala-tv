-- SQLite variant of schema.sql, for local development/testing only.
-- Production uses MySQL via schema.sql (see DB_DRIVER in config/config.php).

CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform TEXT NOT NULL DEFAULT 'youtube',
    channel_id TEXT,
    name TEXT NOT NULL,
    url TEXT,
    api_key TEXT,
    api_secret TEXT,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS thumbnails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    video_id INTEGER NOT NULL UNIQUE,
    background_image TEXT,
    image_1 TEXT,
    image_2 TEXT,
    image_3 TEXT,
    image_4 TEXT,
    image_5 TEXT,
    text_1 TEXT,
    text_2 TEXT,
    text_3 TEXT,
    rendered_image TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_operations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id INTEGER NOT NULL,
    video_path TEXT,
    topic TEXT,
    content TEXT,
    voice_enabled INTEGER NOT NULL DEFAULT 0,
    voice_over_path TEXT,
    title TEXT,
    description TEXT,
    tags TEXT,
    thumbnail_ref INTEGER,
    video_type TEXT CHECK (video_type IN ('long', 'short')),
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'prepared', 'scheduled', 'published', 'failed')),
    youtube_video_id TEXT,
    scheduled_at TEXT,
    published_at TEXT,
    error_message TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (thumbnail_ref) REFERENCES thumbnails(id) ON DELETE SET NULL
);
