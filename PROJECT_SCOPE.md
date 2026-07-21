# Project Scope

## Overview

Magulsakwala-tv is a social media automation web application focused on YouTube channel management. It provides a single-admin, no-login web interface for uploading and scheduling videos, auto-replying to comments, generating content metadata, and viewing channel analytics — built with a React frontend and a PHP backend.

## Goals

- Automate recurring YouTube channel tasks: video uploads/scheduling, comment replies, and content metadata generation.
- Surface channel analytics (views, watch time, subscriber growth) in one place.
- Keep the app simple: single admin, no authentication/login layer.

## Tech Stack

- Frontend: React
- Backend: PHP
- Frontend/backend communication: REST API (JSON)
- Scheduled/background work: PHP cron jobs (video publishing, auto-reply processing)

## Platform Scope

- **YouTube only** (no Instagram, Facebook, or Twitter/X integration).

## Access Model

- Single admin user, no login/authentication required.

## Endpoints

### Videos

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/videos/upload` | Upload a video and optionally schedule its publish time |
| GET | `/api/videos` | List videos (uploaded, scheduled, published) |
| GET | `/api/videos/{id}` | Get details for a single video |
| PUT | `/api/videos/{id}/schedule` | Update a video's scheduled publish time |
| DELETE | `/api/videos/{id}` | Cancel a scheduled upload or remove a video record |

### Comments (Auto-Reply)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/comments` | List recent comments across videos |
| POST | `/api/comments/{id}/reply` | Manually reply to a specific comment |
| GET | `/api/comments/auto-reply-rules` | List configured auto-reply rules |
| POST | `/api/comments/auto-reply-rules` | Create a new auto-reply rule |
| PUT | `/api/comments/auto-reply-rules/{id}` | Update an auto-reply rule |
| DELETE | `/api/comments/auto-reply-rules/{id}` | Delete an auto-reply rule |

### Content Generation

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/content/generate/title` | Generate a video title |
| POST | `/api/content/generate/description` | Generate a video description |
| POST | `/api/content/generate/tags` | Generate SEO tags/keywords |
| POST | `/api/content/generate/thumbnail` | Generate/suggest a thumbnail image |

### Analytics

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/analytics/channel` | Channel-level stats (views, watch time, subscribers) |
| GET | `/api/analytics/videos/{id}` | Per-video performance stats |

### Background Jobs (PHP Cron, not public endpoints)

| Script | Description |
|---|---|
| `cron/process-scheduled-uploads.php` | Publishes videos whose scheduled time has passed |
| `cron/process-auto-replies.php` | Scans new comments and applies matching auto-reply rules |

## Database Schema

### `accounts`

One row per managed YouTube channel (currently ~10 channels), storing the credentials and metadata needed to operate that channel via the YouTube API.

| Column | Type | Description |
|---|---|---|
| `id` | INT, PK, auto-increment | Row identifier |
| `platform` | VARCHAR | Platform flag, e.g. `"youtube"` (supports future multi-platform rows) |
| `channel_id` | VARCHAR | External platform channel/account ID (e.g. YouTube channel ID) |
| `name` | VARCHAR | Channel/account display name |
| `url` | VARCHAR | Channel/account URL |
| `api_key` | VARCHAR | Client key/API key for this account |
| `api_secret` | VARCHAR | Client secret for this account |
| `access_token` | TEXT | Current OAuth access token |
| `refresh_token` | TEXT | OAuth refresh token |
| `token_expires_at` | DATETIME | When `access_token` expires and needs refreshing |
| `created_at` | DATETIME | Row creation timestamp |
| `updated_at` | DATETIME | Last updated timestamp |

### `video_operations`

The main, comprehensive table for videos — one row per video, tracking it through its full lifecycle from upload to publish. Each row references the YouTube channel it belongs to via a foreign key to `accounts`.

| Column | Type | Description |
|---|---|---|
| `id` | INT, PK, auto-increment | Row identifier |
| `account_id` | INT, FK -> `accounts.id` | The YouTube channel this video belongs to |
| `video_path` | VARCHAR | Path/URL to the source video file before upload |
| `title` | VARCHAR | Video title |
| `description` | TEXT | Video description |
| `tags` | TEXT | SEO tags/keywords |
| `thumbnail` | VARCHAR | Path/URL to the thumbnail image |
| `status` | ENUM(`draft`, `scheduled`, `published`, `failed`) | Current lifecycle state of the video |
| `youtube_video_id` | VARCHAR | Video ID returned by YouTube after upload (used for comments/analytics lookups) |
| `scheduled_at` | DATETIME | When the video is scheduled to publish |
| `published_at` | DATETIME | When the video was actually published |
| `error_message` | TEXT | Failure reason, populated when `status = 'failed'` |
| `created_at` | DATETIME | Row creation timestamp |
| `updated_at` | DATETIME | Last updated timestamp |

## Out of Scope

- Python is not used anywhere in this project (no scripts, tooling, or services).
- Node.js is not used anywhere in this project (no npm-based build tooling or Node services).
- No login/authentication system.
- No platforms beyond YouTube for now (Instagram, Facebook, Twitter/X, TikTok, etc. are not included).

## Status

This is a living document, updated directly from project scope conversations. Scope, endpoints, and priorities will continue to evolve as more requirements are discussed.
