-- Migration 0005: zodiac sign video set + Supabase Storage wiring.
-- Applied via Supabase MCP apply_migration to project ddjrybbhhcwuiczwncfc.

-- Private bucket for voice-over audio + final rendered video (not thumbnails,
-- those stay on local disk as before).
INSERT INTO storage.buckets (id, name, public) VALUES ('media', 'media', false)
ON CONFLICT (id) DO NOTHING;

-- Per-channel set of 12 zodiac sign videos, separate table from
-- video_operations (see migration 0002). Auto-seeded (one row per sign)
-- whenever a new account/channel is created — see AccountsController::create.
CREATE TABLE IF NOT EXISTS zodiac_videos (
    id BIGSERIAL PRIMARY KEY,
    account_id BIGINT NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    zodiac_sign VARCHAR(20) NOT NULL CHECK (zodiac_sign IN (
        'aries','taurus','gemini','cancer','leo','virgo',
        'libra','scorpio','sagittarius','capricorn','aquarius','pisces'
    )),
    title VARCHAR(255) NULL,
    description TEXT NULL,
    tags TEXT NULL,
    thumbnail_preset_ref BIGINT NULL REFERENCES m_thumbnails(id) ON DELETE SET NULL,
    voice_over_path VARCHAR(500) NULL,
    rendered_video_path VARCHAR(500) NULL,
    video_type VARCHAR(10) NOT NULL DEFAULT 'short' CHECK (video_type IN ('long', 'short')),
    status VARCHAR(20) NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'prepared', 'scheduled', 'published', 'failed')),
    youtube_video_id VARCHAR(255) NULL,
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (account_id, zodiac_sign)
);

CREATE TRIGGER trg_zodiac_videos_updated_at
    BEFORE UPDATE ON zodiac_videos
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- video_operations: old local-disk voice_over_path kept (renamed) so
-- existing paths remain readable; new column holds Supabase Storage object
-- paths going forward. New rendered_video_path for the ffmpeg output.
ALTER TABLE video_operations RENAME COLUMN voice_over_path TO voice_over_path_legacy;
ALTER TABLE video_operations ADD COLUMN voice_over_path VARCHAR(500) NULL;
ALTER TABLE video_operations ADD COLUMN rendered_video_path VARCHAR(500) NULL;

-- m_content_feeder: support zodiac videos too (exactly one of the two refs).
ALTER TABLE m_content_feeder ALTER COLUMN video_ref DROP NOT NULL;
ALTER TABLE m_content_feeder ADD COLUMN zodiac_video_ref BIGINT NULL REFERENCES zodiac_videos(id) ON DELETE CASCADE;
ALTER TABLE m_content_feeder ADD CONSTRAINT chk_content_feeder_one_ref CHECK (
    (video_ref IS NOT NULL AND zodiac_video_ref IS NULL) OR
    (video_ref IS NULL AND zodiac_video_ref IS NOT NULL)
);
