# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Magulsakwala-tv is a social media automation web application for a Western astrology YouTube channel — content (topics, titles, zodiac-sign videos) is Western astrology (12 signs: Aries–Pisces), not Vedic/Jyotish.

## Tech Stack

- Frontend: React
- Backend: PHP

**Important:** This project does **not** use Node.js. Do not introduce Node.js/npm-based build tooling — all frontend work stays within React (no build step, vendored libraries, Babel-in-browser — see `frontend/public/index.html`) and backend work stays within PHP. Do not add any other language, runtime, or project beyond the one documented exception below (no separate Node.js project, no Docker, etc.).

**Documented exceptions — local Python model servers.** Two models have no PHP implementation, so each runs as a small local Python HTTP server that the PHP backend calls over HTTP (stdlib `http.server`, own venv, no API key needed — both are local). These are the **only** places Python is allowed in this project. Do not introduce Python anywhere else (no Python scripts/tooling in `backend/` or `frontend/`). Model weights and each venv are gitignored — not committed.

- **Kokoro TTS (`kokoro-tts/`):** `kokoro-tts/server.py` (the `kokoro-onnx` package) ↔ `backend/src/Services/KokoroService.php` (`KOKORO_API_URL` in `.env`). Weights: `kokoro-tts/models/*.onnx`, `*.bin`, ~350MB. Run: `kokoro-tts/venv/bin/python3 kokoro-tts/server.py 8200` (must be running for voice-over generation; PHP calls `http://127.0.0.1:8200/tts`).
- **SD-Turbo image generation (`image-gen/`):** `image-gen/server.py` (`torch` + `diffusers`, `stabilityai/sd-turbo`, runs on MPS if available) ↔ `backend/src/Services/ImageGenService.php` (`IMAGEGEN_API_URL` in `.env`), wired into `AssetsController::generate` (`POST /api/assets/{category}/generate`) for the image-based Assets categories. Weights: cached by Hugging Face at `~/.cache/huggingface` (outside the project, ~5GB, downloaded automatically on first run). Run: `image-gen/venv/bin/python3 image-gen/server.py 8300` (must be running for AI asset generation; PHP calls `http://127.0.0.1:8300/generate`). No built-in safety/NSFW filter (sd-turbo ships without one) — fine for this local, non-public tool, but don't expose this endpoint publicly without adding one.

## Database

**Only database: Supabase Postgres.** Project ref `ddjrybbhhcwuiczwncfc`, host `db.ddjrybbhhcwuiczwncfc.supabase.co`, port `5432`, db `postgres`, user `postgres` (password in `backend/.env`, gitignored). `backend/config/database.php` connects via `pgsql` PDO only — no mysql/sqlite driver branches, no fallback. Schema lives in `backend/database/migrations/` (Postgres syntax — `BIGSERIAL`, `CHECK` constraints instead of `ENUM`, `set_updated_at()` trigger instead of `ON UPDATE CURRENT_TIMESTAMP`). Apply new migrations through the Supabase MCP (`apply_migration`), and add the matching `.sql` file to `backend/database/migrations/` for the record.

The old cloud MySQL DB (`209.42.255.1`) and SQLite are retired — not referenced anywhere in this project anymore.

This Supabase project also holds unrelated tables (`m_topics`, `m_topics_langas`) from a different project on the same account — leave those alone.

## Assets

The Assets tab (`frontend/public/src/components/AssetsPage.jsx`) is a reusable image/font library used when building thumbnails — Western astrology categories: Thumbnails, Zodiac Signs, Planets, Elements, Moon Phases, Backgrounds, Fonts. Each category is its own table (`m_zodiac_sign_assets`, `m_planet_assets`, `m_element_assets`, `m_moon_phase_assets`, `m_background_assets`, `m_font_assets` — migrations 0006/0007; `Thumbnails` already has `m_thumbnails` from migration 0003), same shape: `reference_no` (via `ReferenceCodeService`), `channel_id`, `name`, and `image` (or `file` for fonts) holding a local storage path.

**Workflow when given an image file and asked to crop it and place it in assets:** crop the image (e.g. via `sips`/ImageMagick over Bash — not part of the app's own code, just how the crop gets done), save the cropped file to disk under the project's existing per-account storage convention (`storage/accounts/{account_id}/assets/{category}/`, mirroring `StorageService`'s `accounts/{id}/{subfolder}/` layout used for thumbnails/videos/audio), and insert a row into that category's table pointing at the saved path — every asset saved to disk must have a matching row in its table, not just a file sitting in storage.

## Scope Restriction

Only edit files inside this project (Magulsakwala-tv). Other projects read-only — reference only, no write/edit. User may point to file in different project for context/reference; read it, take what needed, but never modify anything outside this project directory.
