# SETUP — Running Magulsakwala-tv on a new Mac

Everything needed to bring this project up on a second machine: system packages, the
two local Python model servers, `backend/.env`, the run commands, and the Claude Code
(agent) setup.

Reference machine this was verified on: macOS (Apple Silicon), PHP 8.5.8, ffmpeg 8.1.2,
Python 3.14.6.

---

## 1. System prerequisites

Install Homebrew first (<https://brew.sh>), then:

```bash
brew install php ffmpeg git python@3.13
```

| Tool | Why | Check |
|---|---|---|
| PHP 8.1+ (8.5 used here) | whole backend + serves the frontend | `php -v` |
| ffmpeg | `VideoRenderService` shells out to it to compose image+audio into MP4 | `ffmpeg -version` |
| Python 3.11+ | only for the two local model servers (`kokoro-tts/`, `image-gen/`) | `python3 -V` |
| git | clone/push | `git --version` |

**No Node.js, no npm, no Docker, no local database server.** React is vendored in
`frontend/public/vendor/` and compiled in-browser by Babel; the database is remote
Supabase Postgres.

Required PHP extensions (all bundled with Homebrew PHP — verify anyway):

```bash
php -m | grep -iE 'pdo_pgsql|pgsql|gd|curl|mbstring|fileinfo|json|zip'
```

- `pdo_pgsql` / `pgsql` — Supabase Postgres connection (`backend/config/database.php`)
- `gd` — thumbnail composition (`ThumbnailRenderService`)
- `curl` — OpenAI, YouTube Data API, Supabase Storage, Kokoro, image-gen
- `mbstring`, `fileinfo`, `json`, `zip` — uploads, text handling

If `pdo_pgsql` is missing, reinstall PHP via Homebrew (it ships it) rather than
hand-compiling.

---

## 2. Clone

```bash
git clone <this repo url> Magulsakwala-tv
cd Magulsakwala-tv
```

What is **not** in the repo (gitignored, must be recreated on the new Mac):

- `backend/.env` — all secrets (section 3)
- `kokoro-tts/venv/` + `kokoro-tts/models/` — ~350MB of ONNX weights (section 4)
- `image-gen/venv/` — SD-Turbo weights cache to `~/.cache/huggingface`, ~5GB (section 5)
- `storage/accounts/*` — generated/uploaded media. Rows in the DB point at these paths,
  so if you want existing thumbnails/assets to resolve on the new Mac, copy
  `storage/accounts/` across from the old machine (rsync/USB). Otherwise the app runs
  fine but old asset images will 404.

---

## 3. `backend/.env`

Create `backend/.env` (gitignored, never committed). Full key list — values come from the
old Mac's `backend/.env` or from the dashboards named below:

```ini
# Supabase Postgres (the only database — nothing to install locally)
DB_HOST=db.ddjrybbhhcwuiczwncfc.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=            # Supabase dashboard > Settings > Database

# Absolute path on THIS Mac — must be updated, the old path won't exist
STORAGE_PATH=/Users/<you>/PROJECTS/Ones n Zeros/YT Astro/Magulsakwala-tv/storage

# OpenAI (text/content generation) — https://platform.openai.com/api-keys
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini

# Local Kokoro TTS server (no API key — it's local)
KOKORO_API_URL=http://127.0.0.1:8200/tts
KOKORO_API_KEY=

# Local SD-Turbo image-gen server (no API key — it's local)
IMAGEGEN_API_URL=http://127.0.0.1:8300/generate

# Supabase Storage (audio + rendered video; thumbnails stay local)
SUPABASE_URL=https://ddjrybbhhcwuiczwncfc.supabase.co
SUPABASE_SERVICE_ROLE_KEY=   # dashboard > Settings > API > service_role (server-side secret)

# Optional overrides (defaults in backend/config/config.php)
# YOUTUBE_OAUTH_REDIRECT_URI=http://localhost:8100/api/oauth/youtube/callback
# FRONTEND_URL=http://localhost:8000
```

`STORAGE_PATH` is the one value that **must** change per machine. Everything else can be
copied verbatim.

---

## 4. Kokoro TTS server (`kokoro-tts/`) — voice-over

```bash
cd kokoro-tts
python3 -m venv venv
venv/bin/python3 -m pip install --upgrade pip
venv/bin/python3 -m pip install kokoro-onnx
mkdir -p models
curl -L -o models/kokoro-v1.0.onnx \
  https://github.com/thewh1teagle/kokoro-onnx/releases/download/model-files-v1.0/kokoro-v1.0.onnx
curl -L -o models/voices-v1.0.bin \
  https://github.com/thewh1teagle/kokoro-onnx/releases/download/model-files-v1.0/voices-v1.0.bin
```

Expected sizes: `kokoro-v1.0.onnx` ~310MB, `voices-v1.0.bin` ~27MB. `server.py` loads both
by exactly those filenames from `kokoro-tts/models/`.

Run (must be up before generating voice-over):

```bash
kokoro-tts/venv/bin/python3 kokoro-tts/server.py 8200
```

Optional env: `KOKORO_VOICE` (default `af_sarah`), `KOKORO_SPEED` (default `1.0`).

---

## 5. SD-Turbo image-gen server (`image-gen/`) — AI asset generation

```bash
cd image-gen
python3 -m venv venv
venv/bin/python3 -m pip install --upgrade pip
venv/bin/python3 -m pip install torch diffusers transformers accelerate safetensors pillow
```

Run:

```bash
image-gen/venv/bin/python3 image-gen/server.py 8300
```

First start downloads `stabilityai/sd-turbo` (~5GB) into `~/.cache/huggingface` — slow
once, instant after. Uses Apple MPS when available, falls back to CPU (much slower).
Endpoints: `GET /info`, `POST /generate`, `POST /img2img`.

Note: sd-turbo ships without a safety/NSFW filter. Fine for this local tool; don't expose
the port publicly.

---

## 6. Database

Nothing to install. The schema already lives in the remote Supabase project
(`ddjrybbhhcwuiczwncfc`) — a new Mac just connects to it. Migrations in
`backend/database/migrations/` are the historical record; they are already applied.

Verify the connection from the new Mac:

```bash
php -r '$c=require "backend/config/config.php";' # (sanity: file parses)
psql "postgresql://postgres:<password>@db.ddjrybbhhcwuiczwncfc.supabase.co:5432/postgres" -c '\dt'
```

(`psql` is optional — `brew install libpq`. The app itself only needs PHP's `pdo_pgsql`.)

New migrations are applied through the Supabase MCP (`apply_migration`), with the matching
`.sql` file added to `backend/database/migrations/`.

---

## 7. Run the app

Four processes. Ports are fixed by `.env` / `index.html` — don't swap them.

| Port | Process | Command |
|---|---|---|
| 8100 | PHP backend API | `php -S 127.0.0.1:8100 -t backend/public` |
| 8000 | Frontend (static) | `php -S 127.0.0.1:8000 -t frontend/public` |
| 8200 | Kokoro TTS | `kokoro-tts/venv/bin/python3 kokoro-tts/server.py 8200` |
| 8300 | SD-Turbo image-gen | `image-gen/venv/bin/python3 image-gen/server.py 8300` |

Open <http://localhost:8000/index.html>. The frontend targets the backend via
`window.API_BASE` at the top of `frontend/public/index.html` (`http://localhost:8100`).

The two Python servers are only needed for voice-over and AI asset generation; the rest of
the app works without them.

Scheduled publishing (optional, per crontab):

```
*/5 * * * * php /absolute/path/to/backend/cron/process-scheduled-uploads.php
```

---

## 8. YouTube accounts

Per-channel OAuth credentials (`api_key` = client ID, `api_secret` = client secret,
`refresh_token`) live in the `accounts` table in Supabase — they come across with the
database, no re-entry needed.

The only machine-specific piece: the Google Cloud OAuth client (Web application type) must
list the redirect URI exactly as configured —
`http://localhost:8100/api/oauth/youtube/callback` by default. If the new Mac serves the
backend on a different host/port, add that URI in Google Cloud Console **and** set
`YOUTUBE_OAUTH_REDIRECT_URI` in `.env` to match.

---

## 9. Agent (Claude Code) setup

1. Install Claude Code and sign in:
   ```bash
   curl -fsSL https://claude.ai/install.sh | bash   # or: brew install --cask claude-code
   claude   # then /login
   ```
2. Run it from the project root. `CLAUDE.md` is committed, so the agent picks up the
   project rules automatically: PHP+React only, no Node/npm/Docker, Python allowed **only**
   in `kokoro-tts/` and `image-gen/`, Supabase Postgres is the only database, the Assets
   workflow, and the scope restriction (never edit files outside this project).
3. Add the Supabase MCP server (used for `apply_migration`, `execute_sql`, `list_tables`).
   It is configured per-user, not in the repo — there is no `.mcp.json` here, so it must be
   added on the new Mac:
   ```bash
   claude mcp add --scope user supabase -- npx -y @supabase/mcp-server-supabase@latest \
     --project-ref=ddjrybbhhcwuiczwncfc
   ```
   Set `SUPABASE_ACCESS_TOKEN` (a personal access token from the Supabase dashboard) in the
   environment for that server. If you'd rather keep the machine Node-free, use the
   Supabase connector on claude.ai instead and skip the CLI MCP server — the project code
   itself never touches npx.
4. Optional but useful context files already in the repo: `PROJECT_SCOPE.md` (full design),
   `PROCEED_DEVELOPMENT.md` (build plan), `PENDING.md` (open work),
   `REQUIRED_CREDENTIALS.md` (credential checklist).
5. `.claude/` in this repo is empty — no project-level settings, hooks, or permissions to
   port. Any hooks/skills/statusline you rely on live in `~/.claude/` on the old Mac; copy
   that directory across if you want the same agent behaviour.

---

## 10. Verification checklist

```bash
php -v && php -m | grep -c pdo_pgsql        # PHP + Postgres driver
ffmpeg -version | head -1                   # ffmpeg present
ls -lh kokoro-tts/models                    # 310MB onnx + 27MB bin
curl -s http://127.0.0.1:8300/info | head -c 200   # image-gen up
curl -s http://127.0.0.1:8100/api/accounts | head -c 200   # backend + DB reachable
```

Then in the browser: accounts list loads, Assets tab shows images, a thumbnail renders, a
voice-over generates (Kokoro up), an AI asset generates (image-gen up).

---

## Known doc drift

`README.md` still describes the retired MySQL/SQLite setup and Gemini TTS, and puts the
frontend on port 8200 (now Kokoro's port). This file supersedes it; `CLAUDE.md` is the
accurate source for architecture rules.
