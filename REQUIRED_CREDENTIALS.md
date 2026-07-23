# Required Credentials

Fill in the blanks below (or just tell me the values in chat) and I'll wire them into `backend/.env` and the `accounts` table. Nothing here is committed to git — `backend/.env` is gitignored, and per-account secrets go straight into the database.

## 1. Database

Where should the app's MySQL database live?

- `DB_HOST`:
- `DB_PORT` (default `3306`):
- `DB_NAME`:
- `DB_USER`:
- `DB_PASSWORD`:

## 2. Local storage path

- `STORAGE_PATH` — absolute path on the server where `storage/accounts/...` should live (leave blank to use the project's own `storage/` folder):

## 3. OpenAI (text content generation)

- `OPENAI_API_KEY` — from https://platform.openai.com/api-keys:
- `OPENAI_MODEL` (default `gpt-4o-mini`, leave blank to use default):

## 4. Google Gemini (voice-over generation)

- `GEMINI_API_KEY` — from https://aistudio.google.com/apikey:
- `GEMINI_TTS_MODEL` (default `gemini-2.5-flash-preview-tts`, leave blank to use default):

## 5. YouTube accounts (one block per channel)

Each connected channel needs its own OAuth client from Google Cloud Console (YouTube Data API v3 enabled) plus a refresh token obtained by completing the OAuth consent flow once for that channel.

For **each** YouTube channel you want to connect, provide:

- Channel display name:
- Channel URL:
- YouTube channel ID:
- OAuth client ID (`api_key`):
- OAuth client secret (`api_secret`):
- OAuth refresh token (`refresh_token`):

(Copy this block for each additional channel.)

---

Once you have any of these, paste them here (or just tell me in chat) and I'll fill in `backend/.env` and create/update the matching `accounts` rows.
