-- Migration 0004: content feeder library (hook / content / cta per video).
-- Applied via Supabase MCP apply_migration to project ddjrybbhhcwuiczwncfc.

CREATE TABLE IF NOT EXISTS m_content_feeder (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    video_ref BIGINT NOT NULL REFERENCES video_operations(id) ON DELETE CASCADE,
    hook TEXT NULL,
    content TEXT NULL,
    cta TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_m_content_feeder_updated_at
    BEFORE UPDATE ON m_content_feeder
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
