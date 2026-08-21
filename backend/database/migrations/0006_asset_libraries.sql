-- Migration 0006: Assets tab asset libraries (Western astrology building
-- blocks reused when composing thumbnails). Same shape as m_thumbnails/
-- m_content_feeder: reference-coded, channel-scoped. One table per category,
-- Thumbnails already has its own table (m_thumbnails, migration 0003).

CREATE TABLE IF NOT EXISTS m_zodiac_sign_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_zodiac_sign_assets_updated_at BEFORE UPDATE ON m_zodiac_sign_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS m_planet_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_planet_assets_updated_at BEFORE UPDATE ON m_planet_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS m_element_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_element_assets_updated_at BEFORE UPDATE ON m_element_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS m_moon_phase_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_moon_phase_assets_updated_at BEFORE UPDATE ON m_moon_phase_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS m_background_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_background_assets_updated_at BEFORE UPDATE ON m_background_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();
