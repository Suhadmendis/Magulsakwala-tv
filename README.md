# Magulsakwala-tv

A single-admin YouTube automation web app (uploads/scheduling, AI-suggested comment replies, live analytics, AI content + voice-over generation, and a thumbnail composer). See `PROJECT_SCOPE.md` for the full design and `PROCEED_DEVELOPMENT.md` for the build plan.

## Stack

React (buildless — in-browser Babel, vendored locally, no npm/Node) + PHP (plain, no framework). No Python anywhere.

## Running locally

**1. Database**

```
mysql -u root -p your_db < backend/database/schema.sql
```

(For quick local testing without MySQL, `backend/database/schema.sqlite.sql` works with `DB_DRIVER=sqlite`.)

**2. Backend**

```
cp backend/.env.example backend/.env   # fill in DB + OPENAI_API_KEY + GEMINI_API_KEY
php -S 127.0.0.1:8100 -t backend/public
```

**3. Frontend**

```
php -S 127.0.0.1:8200 -t frontend/public
```

Open `http://127.0.0.1:8200/index.html`. It talks to the backend via `window.API_BASE`, set at the top of `frontend/public/index.html` (defaults to `http://127.0.0.1:8100`).

**4. Cron (scheduled publishing)**

Add to crontab, running every few minutes:

```
* * * * * php /path/to/backend/cron/process-scheduled-uploads.php
```

Each connected YouTube account needs its `api_key` (OAuth client ID), `api_secret` (client secret), and `refresh_token` set (via the Accounts edit page, or directly in the `accounts` table) before publishing/comments/analytics will work — see `PROJECT_SCOPE.md`.
