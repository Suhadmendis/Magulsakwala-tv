-- Migration 0002: switch source of truth to Supabase Postgres.
-- Ported from 0001_initial_schema.sql (MySQL). Applied via Supabase MCP
-- apply_migration to project ddjrybbhhcwuiczwncfc; kept here for the record.

CREATE TABLE IF NOT EXISTS accounts (
    id BIGSERIAL PRIMARY KEY,
    platform VARCHAR(50) NOT NULL DEFAULT 'youtube',
    channel_id VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NULL,
    api_key VARCHAR(500) NULL,
    api_secret VARCHAR(500) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_operations (
    id BIGSERIAL PRIMARY KEY,
    account_id BIGINT NOT NULL,
    video_path VARCHAR(500) NULL,
    topic VARCHAR(500) NULL,
    content TEXT NULL,
    voice_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    voice_over_path VARCHAR(500) NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    tags TEXT NULL,
    thumbnail_ref BIGINT NULL,
    video_type VARCHAR(10) NULL CHECK (video_type IN ('long', 'short')),
    status VARCHAR(20) NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'prepared', 'scheduled', 'published', 'failed')),
    youtube_video_id VARCHAR(255) NULL,
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_video_operations_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS thumbnails (
    id BIGSERIAL PRIMARY KEY,
    video_id BIGINT NOT NULL UNIQUE,
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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_thumbnails_video FOREIGN KEY (video_id) REFERENCES video_operations(id) ON DELETE CASCADE
);

ALTER TABLE video_operations
    ADD CONSTRAINT fk_video_operations_thumbnail FOREIGN KEY (thumbnail_ref) REFERENCES thumbnails(id) ON DELETE SET NULL;

-- Postgres has no ON UPDATE CURRENT_TIMESTAMP; emulate with a trigger.
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_accounts_updated_at
    BEFORE UPDATE ON accounts
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_video_operations_updated_at
    BEFORE UPDATE ON video_operations
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_thumbnails_updated_at
    BEFORE UPDATE ON thumbnails
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
