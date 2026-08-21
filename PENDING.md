# Pending Setup / Work

Status snapshot — no DB connection made yet, nothing verified against live database.

## Credentials still missing (`backend/.env`)

- `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` — placeholder values (`127.0.0.1`, `magulsakwala_tv`, `root`, blank password). Confirm real MySQL target before first run.
- `STORAGE_PATH` — still `/absolute/path/to/storage` placeholder.
- `OPENAI_API_KEY` — blank. Needed for text content generation.
- `GEMINI_API_KEY` — filled (pulled from `../gemini/.env`, `VITE_GOOGLE_API_KEY`).

## Not started

- YouTube per-channel OAuth blocks (channel name, channel ID, OAuth client id/secret, refresh token) — none provided yet. Needed once per connected channel, written to `accounts` table.
- Database not created/migrated — `backend/database/schema.sql` / `schema.sqlite.sql` exist but not applied to any live DB yet.
- No verification that backend boots against real DB/storage — blocked on above.

## Open questions

- OpenAI key: pull from Gemini project's `.env` (`VITE_OPENAI_API_KEY`) same as Gemini key was, or separate key for this project?
- Real DB host/credentials — local MySQL install, remote host, or Docker?
- `STORAGE_PATH` — use project's own `storage/` folder (leave blank per REQUIRED_CREDENTIALS.md) or a specific absolute path?
