# Graph Report - .  (2026-07-23)

## Corpus Check
- 45 files · ~50,672 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1569 nodes · 4643 edges · 97 communities (68 shown, 29 thin omitted)
- Extraction: 73% EXTRACTED · 27% INFERRED · 0% AMBIGUOUS · INFERRED: 1276 edges (avg confidence: 0.56)
- Token cost: 90,430 input · 0 output

## Community Hubs (Navigation)
- Babel Transpiler Internals (d0-d9 helpers)
- Babel Transpiler Internals (core)
- React DOM Minified Internals (fiber core)
- React DOM Minified Internals (scheduler)
- Comments & Thumbnails Editor Pages
- React DOM Minified Internals (hooks)
- React DOM Minified Internals (reconciler)
- React DOM Minified Internals (events)
- Config & GeminiService (voice-over backend)
- React DOM Minified Internals (misc)
- React DOM Minified Internals (misc 2)
- React DOM Minified Internals (misc 3)
- React DOM Minified Internals (misc 4)
- React DOM Minified Internals (misc 5)
- React DOM Minified Internals (misc 6)
- React DOM Minified Internals (misc 7)
- React DOM Minified Internals (misc 8)
- React DOM Minified Internals (misc 9)
- React DOM Minified Internals (misc 10)
- React Core Minified Internals
- React DOM Minified Internals (misc 11)
- React DOM Minified Internals (misc 12)
- React DOM Minified Internals (misc 13)
- React DOM Minified Internals (misc 14)
- AI Content & Voice-Over Generation Scope (OpenAI + Gemini)
- Project Overview & Tech Stack
- React DOM Minified Internals (misc 15)
- React DOM Minified Internals (misc 16)
- VideosController (video lifecycle API)
- React DOM Minified Internals (misc 17)
- Database Connection & Accounts API
- React DOM Minified Internals (misc 18)
- Compose, Video Ops, Thumbnails & Cron Scope
- Foundation Scope (schema, storage, tech split)
- Router (request dispatch)
- ContentController (video content API)
- React DOM Minified Internals (misc 19)
- Analytics & Comments Feature Scope
- Compose Screen & Component Script Tags
- App Shell & Client-Side Routing (app.jsx)
- React DOM Minified Internals (misc 20)
- CommentsController (comment reply API)
- Babel Transpiler Internals (misc)
- React DOM Minified Internals (misc 21)
- AnalyticsController (channel/video stats API)
- ThumbnailsController (thumbnail CRUD API)
- Babel Transpiler Internals (misc 2)
- Babel Transpiler Internals (misc 3)
- ComposeController (compose flow API)
- PublishService (scheduled publish logic)
- Accounts Scope & Top Bar Account Selector
- VideoOperationsPage (video edit UI)
- FindingsController (findings import API)
- Request (HTTP request wrapper)
- Response (HTTP response wrapper)
- ThumbnailRenderService (image rendering)
- Findings Page Scope
- Babel Transpiler Internals (misc 4)
- Babel Transpiler Internals (misc 5)
- Babel Transpiler Internals (misc 6)
- React DOM Minified Internals (misc 22)
- React DOM Minified Internals (misc 23)
- React DOM Minified Internals (misc 24)
- Babel Transpiler Internals (misc 7)
- React DOM Minified Internals (misc 25)
- React DOM Minified Internals (misc 26)
- React DOM Minified Internals (misc 27)
- React DOM Minified Internals (misc 28)
- Project Scope & README Overview Links
- React DOM Minified Internals (misc 29)
- React DOM Minified Internals (misc 30)
- React DOM Minified Internals (misc 31)
- Babel Transpiler Internals (misc 8)
- Babel Transpiler Internals (misc 9)
- React DOM Minified Internals (misc 32)
- React DOM Minified Internals (misc 33)
- React DOM Minified Internals (misc 34)
- React DOM Minified Internals (misc 35)
- Babel Transpiler Internals (misc 10)
- React DOM Minified Internals (misc 36)
- React DOM Minified Internals (misc 37)
- React DOM Minified Internals (misc 38)
- React DOM Minified Internals (misc 39)
- React DOM Minified Internals (misc 40)
- Babel Transpiler Internals (misc 11)
- REST API Boundary Principle

## God Nodes (most connected - your core abstractions)
1. `fse()` - 317 edges
2. `a()` - 153 edges
3. `o()` - 152 edges
4. `n()` - 124 edges
5. `s()` - 102 edges
6. `i()` - 101 edges
7. `p()` - 89 edges
8. `d()` - 88 edges
9. `c()` - 80 edges
10. `m()` - 78 edges

## Surprising Connections (you probably didn't know these)
- `Buildless React + Plain PHP Stack` --semantically_similar_to--> `Batch 1 — Foundation`  [INFERRED] [semantically similar]
  README.md → PROCEED_DEVELOPMENT.md
- `frontend/public/index.html Entry Point` --implements--> `React (Frontend Tech)`  [INFERRED]
  frontend/public/index.html → CLAUDE.md
- `Development Notes (No Python/Node, No Caching, Single Admin)` --conceptually_related_to--> `React (Frontend Tech)`  [INFERRED]
  PROCEED_DEVELOPMENT.md → CLAUDE.md
- `Out of Scope Constraints` --conceptually_related_to--> `React (Frontend Tech)`  [INFERRED]
  PROJECT_SCOPE.md → CLAUDE.md
- `src/components/TopBar.jsx (script tag)` --implements--> `Top Bar (Account Select Box)`  [INFERRED]
  frontend/public/index.html → PROJECT_SCOPE.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **YouTube Account Credential Setup Flow** — required_credentials_youtube_oauth, project_scope_accounts_table, readme_account_credentials_note, frontend_public_index_accounteditpage [INFERRED 0.85]
- **AI Content + Voice-Over Generation Pipeline** — project_scope_openai, project_scope_gemini, project_scope_content_generation_endpoints, project_scope_video_operations_table, proceed_development_batch6_ai_content, proceed_development_batch7_ai_voiceover [INFERRED 0.85]
- **Video Lifecycle (Creation to Publish) Pages** — project_scope_video_operations_page, project_scope_compose_screen, project_scope_findings_page, project_scope_status_workflow, project_scope_video_operations_table [INFERRED 0.85]

## Communities (97 total, 29 thin omitted)

### Community 0 - "Babel Transpiler Internals (d0-d9 helpers)"
Cohesion: 0.01
Nodes (298): d0(), d1(), d2(), d3(), d4(), d5(), d6(), d7() (+290 more)

### Community 1 - "Babel Transpiler Internals (core)"
Cohesion: 0.01
Nodes (56): aw(), bG(), br(), cj(), Cy(), dre(), dt(), eM() (+48 more)

### Community 2 - "React DOM Minified Internals (fiber core)"
Cohesion: 0.05
Nodes (79): a(), aa(), ai(), aM(), as(), bh(), bo(), bu() (+71 more)

### Community 3 - "React DOM Minified Internals (scheduler)"
Cohesion: 0.05
Nodes (32): lj(), pc(), $a(), Ab(), Bd(), Bg(), Cg(), dd() (+24 more)

### Community 4 - "Comments & Thumbnails Editor Pages"
Cohesion: 0.14
Nodes (44): CommentsPage(), ThumbnailsEditorPage(), bc(), c(), Cq(), Cye(), d(), dc() (+36 more)

### Community 5 - "React DOM Minified Internals (hooks)"
Cohesion: 0.23
Nodes (43): ae(), Aq(), b(), Ba(), bv(), _e(), ee(), Epe() (+35 more)

### Community 6 - "React DOM Minified Internals (reconciler)"
Cohesion: 0.10
Nodes (37): Ga(), ud(), xc(), yc(), B(), Bf(), Bk(), cf() (+29 more)

### Community 7 - "React DOM Minified Internals (events)"
Cohesion: 0.09
Nodes (37): gk(), af(), Ag(), Cc(), cd(), df(), Gk(), $h() (+29 more)

### Community 8 - "Config & GeminiService (voice-over backend)"
Cohesion: 0.08
Nodes (5): Config, GeminiService, OpenAIService, StorageService, YouTubeService

### Community 9 - "React DOM Minified Internals (misc)"
Cohesion: 0.13
Nodes (30): aF(), bP(), CF(), fB(), fF(), gB(), gF(), gP() (+22 more)

### Community 10 - "React DOM Minified Internals (misc 2)"
Cohesion: 0.12
Nodes (27): ai(), bi(), ci(), Dg(), ef(), Eh(), fb(), hb() (+19 more)

### Community 11 - "React DOM Minified Internals (misc 3)"
Cohesion: 0.14
Nodes (24): Ble(), cw(), dw(), eu(), is(), jv(), kt(), lw() (+16 more)

### Community 12 - "React DOM Minified Internals (misc 4)"
Cohesion: 0.12
Nodes (23): ede(), er(), es(), Hn(), Hq(), Ide(), ju(), jW() (+15 more)

### Community 13 - "React DOM Minified Internals (misc 5)"
Cohesion: 0.13
Nodes (23): bh(), Ce(), ch(), Ck(), dh(), ed(), fc(), Ie() (+15 more)

### Community 14 - "React DOM Minified Internals (misc 6)"
Cohesion: 0.12
Nodes (22): bi(), bW(), ei(), Fge(), fi(), hW(), it(), ms() (+14 more)

### Community 15 - "React DOM Minified Internals (misc 7)"
Cohesion: 0.13
Nodes (21): Wa(), bc(), be(), di(), ec(), Fg(), fj(), Hg() (+13 more)

### Community 16 - "React DOM Minified Internals (misc 8)"
Cohesion: 0.15
Nodes (22): $g(), gi(), hi(), ia(), ic(), Id(), ii(), ji() (+14 more)

### Community 17 - "React DOM Minified Internals (misc 9)"
Cohesion: 0.12
Nodes (20): afe(), Fw(), Fye(), Gn(), le(), mN(), o(), os() (+12 more)

### Community 18 - "React DOM Minified Internals (misc 10)"
Cohesion: 0.19
Nodes (20): Bq(), bs(), cs(), cu(), DG(), Dq(), Fce(), fs() (+12 more)

### Community 19 - "React Core Minified Internals"
Cohesion: 0.15
Nodes (12): B(), fa(), N(), O(), oa(), p(), pa(), Q() (+4 more)

### Community 20 - "React DOM Minified Internals (misc 11)"
Cohesion: 0.16
Nodes (19): Cde(), de(), ds(), ge(), gs(), Hg(), lr(), noe() (+11 more)

### Community 21 - "React DOM Minified Internals (misc 12)"
Cohesion: 0.22
Nodes (15): av(), cv(), dr(), dv(), fc(), fv(), gv(), iv() (+7 more)

### Community 22 - "React DOM Minified Internals (misc 13)"
Cohesion: 0.15
Nodes (15): ce(), CG(), ic(), ie(), iG(), iu(), jr(), Mge() (+7 more)

### Community 23 - "React DOM Minified Internals (misc 14)"
Cohesion: 0.20
Nodes (14): bj(), $d(), Db(), fi(), Ha(), kg(), le(), lg() (+6 more)

### Community 24 - "AI Content & Voice-Over Generation Scope (OpenAI + Gemini)"
Cohesion: 0.19
Nodes (14): Batch 6 — AI Content Generation (OpenAI), Batch 7 — AI Voice-Over (Gemini), Content Generation Endpoints, Google Gemini (Voice-Over Generation), Project Goals, OpenAI (Text Content Generation), Single-Admin, No-Login Access Model, Backend Setup Instructions (+6 more)

### Community 25 - "Project Overview & Tech Stack"
Cohesion: 0.19
Nodes (13): Magulsakwala-tv (Project Overview), PHP (Backend Tech), React (Frontend Tech), window.API_BASE Config, src/api.js, frontend/public/index.html Entry Point, src/components/TopBar.jsx (script tag), vendor/babel.min.js (+5 more)

### Community 26 - "React DOM Minified Internals (misc 15)"
Cohesion: 0.19
Nodes (13): at(), be(), Dge(), Gh(), gu(), he(), Oge(), or() (+5 more)

### Community 27 - "React DOM Minified Internals (misc 16)"
Cohesion: 0.18
Nodes (13): fr(), fu(), Gfe(), Hfe(), ir(), kfe(), Lfe(), pu() (+5 more)

### Community 29 - "React DOM Minified Internals (misc 17)"
Cohesion: 0.17
Nodes (12): aN(), Bn(), CN(), DN(), EN(), fN(), IN(), Nn() (+4 more)

### Community 30 - "Database Connection & Accounts API"
Cohesion: 0.27
Nodes (3): Database, AccountsController, PDO

### Community 31 - "React DOM Minified Internals (misc 18)"
Cohesion: 0.24
Nodes (11): bk(), Dk(), fk(), HA(), hk(), ok(), pk(), VA() (+3 more)

### Community 32 - "Compose, Video Ops, Thumbnails & Cron Scope"
Cohesion: 0.20
Nodes (11): Batch 12 — Scheduled Publishing (Cron), Batch 4 — Video Operations, Batch 5 — Thumbnails, Batch 8 — Compose Screen, Compose Endpoints, PHP Cron Jobs (Scheduled/Background Work), cron/process-scheduled-uploads.php, Video Status Workflow (draft→prepared→scheduled→published/failed) (+3 more)

### Community 33 - "Foundation Scope (schema, storage, tech split)"
Cohesion: 0.29
Nodes (11): Batch 1 — Foundation, accounts Table, Local File Storage Structure (storage/accounts/{id}/...), PHP Backend (Scope), React Frontend (Scope), thumbnails Table, video_operations Table, Per-Account Credentials Requirement Note (+3 more)

### Community 36 - "React DOM Minified Internals (misc 19)"
Cohesion: 0.20
Nodes (10): ad(), Eg(), Gc(), Hk(), If(), oj(), Rk(), uj() (+2 more)

### Community 37 - "Analytics & Comments Feature Scope"
Cohesion: 0.22
Nodes (9): src/components/AnalyticsPage.jsx (script tag), src/components/CommentsPage.jsx (script tag), Batch 10 — Comments, Batch 11 — Analytics, Analytics Endpoints, Analytics Feature (Live YouTube Fetch), Comments Endpoints, Comments Feature (Live YouTube Fetch, AI-Suggested Replies) (+1 more)

### Community 38 - "Compose Screen & Component Script Tags"
Cohesion: 0.28
Nodes (9): src/app.jsx (script tag), src/components/ComposeScreen.jsx (script tag), src/components/Sidebar.jsx (script tag), src/components/ThumbnailsEditor.jsx (script tag), src/components/VideoOperationsPage.jsx (script tag), Compose Screen, Left Sidebar Navigation, Thumbnails Editor Tool (+1 more)

### Community 39 - "App Shell & Client-Side Routing (app.jsx)"
Cohesion: 0.31
Nodes (5): AccountContext, App(), getCurrentPage(), getRouteParam(), PAGES

### Community 40 - "React DOM Minified Internals (misc 20)"
Cohesion: 0.39
Nodes (8): aa(), mf(), mi(), nf(), qb(), Sb(), Ue(), zf()

### Community 42 - "Babel Transpiler Internals (misc)"
Cohesion: 0.29
Nodes (7): ade(), ar(), Joe(), nde(), sde(), Woe(), zoe()

### Community 43 - "React DOM Minified Internals (misc 21)"
Cohesion: 0.33
Nodes (7): Ma(), Na(), Oa(), qa(), YA(), yr(), ah()

### Community 46 - "Babel Transpiler Internals (misc 2)"
Cohesion: 0.33
Nodes (6): ao(), Eie(), lt(), mo(), Tie(), wie()

### Community 47 - "Babel Transpiler Internals (misc 3)"
Cohesion: 0.33
Nodes (6): hbe(), jbe(), mbe(), OF(), wbe(), ybe()

### Community 50 - "Accounts Scope & Top Bar Account Selector"
Cohesion: 0.40
Nodes (5): src/components/AccountEditPage.jsx (script tag), Batch 2 — Accounts, Batch 3 — Core Layout & Sitemap, Accounts Endpoints, Top Bar (Account Select Box)

### Community 51 - "VideoOperationsPage (video edit UI)"
Cohesion: 0.70
Nodes (4): statusPillClass(), VideoEditForm(), VideoOperationsPage(), VideoSearchDialog()

### Community 56 - "Findings Page Scope"
Cohesion: 0.50
Nodes (4): src/components/FindingsPage.jsx (script tag), Batch 9 — Findings Page, Findings Import Endpoint, Findings Page

### Community 57 - "Babel Transpiler Internals (misc 4)"
Cohesion: 0.50
Nodes (4): ec(), Fq(), lu(), ude()

### Community 58 - "Babel Transpiler Internals (misc 5)"
Cohesion: 0.50
Nodes (4): gy(), my(), no(), parse()

### Community 59 - "Babel Transpiler Internals (misc 6)"
Cohesion: 0.50
Nodes (4): Jme(), Nme(), Vme(), zme()

### Community 60 - "React DOM Minified Internals (misc 22)"
Cohesion: 0.67
Nodes (3): aB(), rB(), tB()

### Community 61 - "React DOM Minified Internals (misc 23)"
Cohesion: 0.67
Nodes (3): Bf(), Mf(), Nf()

### Community 62 - "React DOM Minified Internals (misc 24)"
Cohesion: 0.67
Nodes (3): bx(), vx(), xx()

### Community 63 - "Babel Transpiler Internals (misc 7)"
Cohesion: 0.67
Nodes (3): Goe(), Loe(), Uoe()

### Community 64 - "React DOM Minified Internals (misc 25)"
Cohesion: 0.67
Nodes (3): Gx(), Hx(), Nx()

### Community 65 - "React DOM Minified Internals (misc 26)"
Cohesion: 1.00
Nodes (3): Jf(), wF(), xF()

### Community 66 - "React DOM Minified Internals (misc 27)"
Cohesion: 0.67
Nodes (3): Lx(), Ox(), qx()

### Community 67 - "React DOM Minified Internals (misc 28)"
Cohesion: 0.67
Nodes (3): ne(), vu(), xu()

### Community 68 - "Project Scope & README Overview Links"
Cohesion: 1.00
Nodes (3): Sequential Batch Build Order, Project Scope Overview, README Overview

## Knowledge Gaps
- **15 isolated node(s):** `AccountContext`, `Project Goals`, `React Frontend (Scope)`, `PHP Backend (Scope)`, `REST API (JSON) Communication` (+10 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **29 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `xk()` connect `React DOM Minified Internals (reconciler)` to `React DOM Minified Internals (scheduler)`, `Comments & Thumbnails Editor Pages`, `React DOM Minified Internals (hooks)`, `React DOM Minified Internals (events)`, `React DOM Minified Internals (misc 2)`, `React DOM Minified Internals (misc 8)`, `React Core Minified Internals`, `React DOM Minified Internals (misc 11)`, `React DOM Minified Internals (misc 14)`?**
  _High betweenness centrality (0.042) - this node is a cross-community bridge._
- **Why does `h()` connect `React DOM Minified Internals (hooks)` to `Babel Transpiler Internals (core)`, `React DOM Minified Internals (fiber core)`, `Comments & Thumbnails Editor Pages`, `React DOM Minified Internals (reconciler)`, `React DOM Minified Internals (events)`, `React DOM Minified Internals (misc)`, `React DOM Minified Internals (misc 2)`, `React DOM Minified Internals (misc 3)`, `React DOM Minified Internals (misc 5)`, `React DOM Minified Internals (misc 7)`, `React DOM Minified Internals (misc 9)`, `Babel Transpiler Internals (misc 6)`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **Why does `c()` connect `Comments & Thumbnails Editor Pages` to `Babel Transpiler Internals (core)`, `React DOM Minified Internals (fiber core)`, `React DOM Minified Internals (hooks)`, `React DOM Minified Internals (reconciler)`, `React DOM Minified Internals (events)`, `React DOM Minified Internals (misc 20)`, `React DOM Minified Internals (misc)`, `React DOM Minified Internals (misc 2)`, `React DOM Minified Internals (misc 3)`, `React DOM Minified Internals (misc 5)`, `Babel Transpiler Internals (misc 2)`, `React DOM Minified Internals (misc 6)`, `React DOM Minified Internals (misc 8)`, `React DOM Minified Internals (misc 9)`, `React DOM Minified Internals (misc 10)`, `React DOM Minified Internals (misc 11)`, `React DOM Minified Internals (misc 14)`, `Babel Transpiler Internals (misc 5)`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **Are the 314 inferred relationships involving `fse()` (e.g. with `d0()` and `d1()`) actually correct?**
  _`fse()` has 314 INFERRED edges - model-reasoned connections that need verification._
- **Are the 101 inferred relationships involving `a()` (e.g. with `c()` and `d()`) actually correct?**
  _`a()` has 101 INFERRED edges - model-reasoned connections that need verification._
- **Are the 61 inferred relationships involving `o()` (e.g. with `ade()` and `afe()`) actually correct?**
  _`o()` has 61 INFERRED edges - model-reasoned connections that need verification._
- **Are the 90 inferred relationships involving `n()` (e.g. with `aa()` and `aB()`) actually correct?**
  _`n()` has 90 INFERRED edges - model-reasoned connections that need verification._