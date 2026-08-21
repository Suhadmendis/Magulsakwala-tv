ALTER TABLE zodiac_videos DROP CONSTRAINT zodiac_videos_status_check;
ALTER TABLE zodiac_videos ADD CONSTRAINT zodiac_videos_status_check
  CHECK (status IN ('draft', 'content_added', 'prepared', 'scheduled', 'published', 'failed'));
