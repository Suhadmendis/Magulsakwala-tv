# Proceed Development

This breaks the full scope from `PROJECT_SCOPE.md` into sequential, buildable batches. Each batch only depends on the ones before it, so they can be developed and tested in order. See `PROJECT_SCOPE.md` for the full reference (schema, endpoint list, layout details) — this file is the build order.

## Batch 1 — Foundation

- [ ] Scaffold the React frontend and PHP backend (REST API only, no framework/tooling outside React + PHP conventions).
- [ ] Create the database and the three core tables:
  - `accounts`
  - `video_operations`
  - `thumbnails`
- [ ] Set up the local storage folder structure: `storage/accounts/{account_id}/{videos,thumbnails}/`.

## Batch 2 — Accounts

- [ ] `GET /api/accounts`, `POST /api/accounts`, `GET /api/accounts/{id}`, `PUT /api/accounts/{id}`, `DELETE /api/accounts/{id}`.
- [ ] `POST /api/accounts` also provisions that account's storage folder on disk.
- [ ] Top bar: YouTube account select box (right side only, no logo/nav).
- [ ] Account dropdown: lists all accounts, each row has an edit button leading to a prefilled account edit page.
- [ ] No login/authentication anywhere in the app.

## Batch 3 — Core Layout & Sitemap

- [ ] Left sidebar with: Video Operations, Comments, Analytics, Thumbnails, Findings, and a Compose button.
- [ ] Wire "currently selected account" as shared app state; every list/detail request scopes to it.

## Batch 4 — Video Operations

- [ ] `video_operations` CRUD: `POST /api/videos/upload`, `GET /api/videos`, `GET /api/videos/{id}`, `PUT /api/videos/{id}`, `PUT /api/videos/{id}/schedule`, `DELETE /api/videos/{id}`.
- [ ] Status workflow: `draft` → `prepared` → `scheduled` → `published`/`failed`, with the `draft` → `prepared` validation gate (title, description, tags, thumbnail_ref, video_type, video_path all required).
- [ ] `video_type` flag (`long`/`short`), set manually during draft editing.
- [ ] Video Operations page UI: video list, no upload button (that's on Compose), Search popup restricted to `draft`/`prepared`/`scheduled` with filters, selecting a result loads it into the edit form.
- [ ] `GET /api/videos/search` backing the search dialog.

## Batch 5 — Thumbnails

- [ ] `thumbnails` table CRUD: `GET /api/thumbnails`, `POST /api/thumbnails`, `DELETE /api/thumbnails/{id}`.
- [ ] Thumbnail editor tool UI: background image + 5 overlay image slots + 3 text elements, producing a `rendered_image`.
- [ ] Wire `video_operations.thumbnail_ref` to the created thumbnail.

## Batch 6 — AI Content Generation (OpenAI)

- [ ] OpenAI service wrapper (single place the rest of the app calls through).
- [ ] `POST /api/content/generate/title`, `.../description`, `.../tags`, `.../content` — all driven by the video's `topic`.

## Batch 7 — AI Voice-Over (Gemini)

- [ ] Gemini service wrapper.
- [ ] `POST /api/content/generate/voice-over` — takes `content`, produces narration audio, saves it under the account's storage folder, and sets `voice_over_path`. Only relevant when `voice_enabled = 1`.

## Batch 8 — Compose Screen

- [ ] Upload/create-video entry point (the only place videos are created).
- [ ] `GET /api/compose/next` — fetch the single `scheduled` video whose `scheduled_at` is closest to now.
- [ ] Preview UI: rendered thumbnail + video details.
- [ ] Post button + `POST /api/videos/post-next` (takes `account_id`) — publishes that video to YouTube immediately, sets `status = published`, `youtube_video_id`, `published_at`. Also usable as a standalone external REST call.

## Batch 9 — Findings Page

- [ ] Textarea + Post button UI for pasting a JSON payload (`{ "elements": [{ "topic": "..." }] }`).
- [ ] `POST /api/findings/import` — bulk-creates `draft` `video_operations` rows, one per element, `topic` set, `account_id` auto-filled from the selected account.

## Batch 10 — Comments

- [ ] `GET /api/comments` — live-fetch un-replied comments from YouTube for the selected account, oldest first (no local table).
- [ ] `POST /api/comments/{youtube_comment_id}/suggest-reply` — AI-suggested reply (does not send).
- [ ] `POST /api/comments/{youtube_comment_id}/reply` — manual confirm-and-send.
- [ ] Comments page UI: list, suggest button, editable reply box, Confirm button.

## Batch 11 — Analytics

- [ ] `GET /api/analytics/channel`, `GET /api/analytics/videos/{id}` — both live-fetched from YouTube, no caching/table.
- [ ] Analytics page UI: channel-level stats + per-video stats.

## Batch 12 — Scheduled Publishing (Cron)

- [ ] `cron/process-scheduled-uploads.php` — publishes any `scheduled` video whose `scheduled_at` has passed, mirroring the logic in `post-next` (sets `published`/`failed`, `youtube_video_id`, `published_at`, `error_message`).

## Notes

- No Python, no Node.js, anywhere in this project.
- No caching for comments or analytics — always live from the YouTube API.
- Single admin, no login/auth layer at any batch.
