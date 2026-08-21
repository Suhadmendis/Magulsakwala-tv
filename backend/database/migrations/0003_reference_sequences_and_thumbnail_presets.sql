-- Migration 0003: reusable reference-code generator + thumbnail preset library.
-- Applied via Supabase MCP apply_migration to project ddjrybbhhcwuiczwncfc.

-- Generic PREFIX-00000001 code allocator, reusable by any future entity
-- (call: SELECT next_reference('thum'), SELECT next_reference('vid'), ...).
-- Atomic under concurrent requests via INSERT ... ON CONFLICT ... RETURNING.
CREATE TABLE IF NOT EXISTS reference_sequences (
    prefix VARCHAR(20) PRIMARY KEY,
    next_value BIGINT NOT NULL DEFAULT 1
);

CREATE OR REPLACE FUNCTION next_reference(p_prefix TEXT, p_pad INT DEFAULT 8)
RETURNS TEXT AS $$
DECLARE
    v_next BIGINT;
BEGIN
    INSERT INTO reference_sequences (prefix, next_value) VALUES (p_prefix, 2)
    ON CONFLICT (prefix) DO UPDATE SET next_value = reference_sequences.next_value + 1
    RETURNING next_value - 1 INTO v_next;
    RETURN p_prefix || '-' || LPAD(v_next::TEXT, p_pad, '0');
END;
$$ LANGUAGE plpgsql;

-- Master thumbnail preset library, scoped per channel (accounts.id).
-- Decoupled from video_operations: unlike `thumbnails` (1:1 per video),
-- each save here is a new reusable preset row.
CREATE TABLE IF NOT EXISTS m_thumbnails (
    id BIGSERIAL PRIMARY KEY,
    reference_no VARCHAR(20) NOT NULL UNIQUE,
    channel_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
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
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_m_thumbnails_updated_at
    BEFORE UPDATE ON m_thumbnails
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
