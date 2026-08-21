-- Migration 0007: Fonts asset library (same shape as migration 0006's asset
-- tables, but a font file instead of an image).

CREATE TABLE IF NOT EXISTS m_font_assets (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(100) NULL,
    file VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trg_font_assets_updated_at BEFORE UPDATE ON m_font_assets FOR EACH ROW EXECUTE FUNCTION set_updated_at();
