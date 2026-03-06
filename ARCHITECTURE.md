# 🏗️ ARCHITECTURE

> System design, component breakdown, and key technical decisions.

---

## Tech Stack Overview

**Laravel 11 + Filament v3 + Laravel AI SDK + PostgreSQL + Redis**

| Layer | Technology |
|---|---|
| Backend framework | Laravel 11 (PHP) |
| Admin panel | Filament v3 (TALL stack: Tailwind + Alpine + Livewire + Laravel) |
| AI | `laravel/ai` — unified SDK for text, agents, conversations, images, voice, embeddings |
| Database | PostgreSQL (with pgvector for future semantic search) |
| Queue | Redis + Laravel Horizon |
| Real-time | Laravel Reverb (WebSockets, Phase 5) |
| Mobile API | Laravel Sanctum (Flutter app, Phase 6) |

→ Full details in [`docs/TECH-STACK.md`](docs/TECH-STACK.md)

---

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Command Center                           │
│                                                                 │
│   ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│   │   Input      │────▶│   Laravel    │────▶│  War Room     │  │
│   │   Parser     │     │   Backend    │     │  Dashboard    │  │
│   │ voice/img/   │     │              │     │  (Livewire/   │  │
│   │   text       │     │   Redis Q    │     │   Vue)        │  │
│   └──────────────┘     └──────┬───────┘     └───────────────┘  │
│                               │                                 │
│                    ┌──────────┼──────────┐                      │
│                    ▼          ▼          ▼                      │
│             ┌────────────┐  ┌─────────┐  ┌──────────────┐      │
│             │  GitHub    │  │   LLM   │  │  Context     │      │
│             │  API Layer │  │  Layer  │  │  Manager     │      │
│             │  (repos/   │  │ OpenAI/ │  │ (PROJECT.md) │      │
│             │  issues/   │  │Anthropic│  │              │      │
│             │   PRs)     │  └─────────┘  └──────────────┘      │
│             └────────────┘                                      │
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │       GitHub         │
                    │  Repos / Issues / PRs│
                    │   (Source of Truth)  │
                    └──────────────────────┘
```

---

## Components

### 📋 Project Registry
- Filament resource — maintains a list of all managed projects
- Stores repo URL, stack, status, assigned agent, and last activity
- Source of truth for the War Room dashboard
- Backed by `projects/_REGISTRY.md` and database

### 📝 Task Manager
- Filament resource with fast input — quickly capture tasks from any source
- Linked to projects, customers, and GitHub issues
- Tracks type (bug / feature / change), priority, status, and source (manual / ai-chat)
- Stores the original raw input alongside the structured task

### 👥 Customer Manager
- Filament resource — tracks clients tied to projects
- Stores name, email, company, notes
- Links to projects and tasks (customer feedback → tasks)

### 💬 Task AI Chat
- Custom Filament page powered by `laravel/ai` Agent
- Uses `RemembersConversations` trait — conversations are persisted and resumable
- Accepts text, voice (transcription via `laravel/ai`), and images (vision via `laravel/ai`)
- Confirms tasks with the founder before writing to the database
- Structured output extracts clean task objects from messy input

### 🧠 Context Manager
- Reads and writes `PROJECT.md` for each project
- Structures and stores:
  - Confirmed decisions
  - Completed work
  - Current task state
  - Open questions
- Ensures agents never work from a blank slate
- Called before and after every agent action

### 🤖 Agent Engine
- Phase 4 — full `laravel/ai` agents with custom tools
- Reads `PROJECT.md` to load full context before every task
- Understands codebase, generates structured code tasks, opens PRs
- Context-aware prompt engineering: project context + task scope + boundaries + decision history + guardrails
- Reports back to Command Center with progress and blockers
- Never acts without reading context first

### 🔗 GitHub Sync Service
- Laravel HTTP client + GitHub REST API
- Syncs repos, issues, and PRs bidirectionally
- Tasks ↔ GitHub Issues, PRs = progress updates
- Background sync via Redis queues

### 📺 War Room Dashboard
- Filament dashboard with real-time widgets via Laravel Reverb (Phase 5)
- Real-time view of all active projects
- Shows: agent status, open PRs, blocked items, recent activity
- The primary daily-driver interface for the founder

### 🗣️ Input Parser
- Captures and normalises diverse input types via `laravel/ai`:
  - **Voice notes** → transcribed via `laravel/ai` audio transcription (Whisper, ElevenLabs, Mistral)
  - **Screenshots** → analysed via `laravel/ai` image analysis (bug reports, design feedback, mockups)
  - **Text / brain dumps** → parsed and broken into discrete tasks
- All inputs flow into Task AI Chat before tasks are confirmed and stored

### 📱 Mobile API
- Sanctum-protected endpoints for Flutter mobile app (Phase 6)
- Primary use: Quick Task AI Chat on the go
- Same backend, same AI logic — lightweight mobile client on top

---

## 🤖 Two AI Layers

### AI Layer 1 — Phase 2 (Lightweight)

**Tool:** `laravel/ai` for Task AI Chat

- LLM API calls to interpret text, images, and voice from the founder or customers
- `Agent` + `RemembersConversations` — conversations are persisted in `agent_conversations` table, resumable across sessions
- **Structured Output** — JSON schema responses extract clean task objects from messy brain dumps or customer feedback
- **Transcription** — voice notes converted to text via Whisper / ElevenLabs / Mistral
- **Image Analysis** — screenshots analysed for bug reports, UI feedback, or design mockups
- Founder confirms tasks before they're stored → no phantom tasks

### AI Layer 2 — Phase 4 (Full Agent Engine)

**Tool:** Full `laravel/ai` agents with custom tools

- Context-aware prompt engineering system that reads PROJECT.md before every action
- Custom tools: read files, write files, create branches, open PRs
- Generates structured code tasks, writes implementation, opens scoped PRs
- Understands the codebase — not just the task description
- Agent self-validates before every PR (scope compliance check)
- Conversations and decisions are persisted via `RemembersConversations`

---

## Data Flow

How a task goes from idea to merged code:

```
1. CAPTURE
   Developer has an idea / spots a bug / receives client feedback
   └─▶ Input Parser (voice / screenshot / text)

2. STRUCTURE
   Input Parser → LLM → structured GitHub issue
   └─▶ Issue created in correct project repo

3. ASSIGN
   Command Center reads new issue
   └─▶ Agent Engine assigns to correct project agent

4. CONTEXT LOAD
   Agent reads PROJECT.md
   └─▶ Full context: decisions, stack, conventions, current state

5. IMPLEMENT
   Agent creates branch, writes code, tests locally
   └─▶ Never touches main branch

6. DELIVER
   Agent opens PR with clear description referencing issue
   └─▶ PR links back to issue, explains approach

7. REVIEW
   Developer reviews PR in War Room or directly on GitHub
   └─▶ Approve → merge, or request changes → agent updates

8. LOG
   Context Manager writes outcome to PROJECT.md
   └─▶ Decision logged, task marked complete, lessons noted
```

---

## 🧩 Prompt Engineering System

Command Center is fundamentally a **structured prompt engineering system**. Every task given to an agent is NOT just "hey fix this" — it's a carefully assembled prompt built from multiple sources of context.

A task prompt is constructed from:

- **Project context** — sourced from `PROJECT.md`: stack, conventions, decisions, current state
- **Task scope** — what exactly needs to change (and nothing else)
- **Boundaries** — what NOT to change, what to preserve
- **Decision history** — what was already decided and confirmed, so the agent doesn't relitigate
- **Guardrails and rules** — the non-negotiable constraints every agent follows

This structured approach is what separates Command Center from just "chatting with an AI". The quality of the output is a direct function of the quality of the context provided.

---

## 🛡️ Edit Guardrails

AI tools break things when editing because they assume and change things they weren't asked to change. Command Center enforces strict edit discipline:

- **🎯 Scoped changes only:** Agent NEVER touches what it wasn't asked to touch. If the task says "fix the login bug", the agent does not refactor the auth module while it's in there.
- **✅ Pre-PR self-validation:** Before opening a PR, agent checks: "Did I only change what was asked? Did I break anything?" A diff is compared against the task scope. If anything is outside scope, it gets reverted.
- **📦 Small, scoped PRs:** Every PR should be reviewable in 60 seconds. No 50-file dumps. One concern per PR. If a task requires touching 20 files, it's probably too broad — break it down.
- **📖 Context-first:** Before any edit, agent reads `PROJECT.md`, the specific files being changed, and the task scope. No cold starts, no assumptions.

---

## 🔍 Review Pipeline

Every change goes through two layers of review before it's considered done:

**Layer 1 — Before PR (automated):**
- Agent self-validates against task scope
- Checks: did I only change what was asked?
- Checks: does anything obviously break?
- If validation fails → agent reverts out-of-scope changes and re-validates

**Layer 2 — After PR (human):**
- Founder reviews the diff in GitHub
- This is the power of Git — you see exactly what changed, line by line
- Approve → merge, or request changes → agent updates
- Nothing reaches `main` without human sign-off

---

## 📦 Data Models

### User
| Field | Type | Notes |
|---|---|---|
| name | string | |
| email | string | unique |
| role | enum | admin / manager |

### Customer
| Field | Type | Notes |
|---|---|---|
| name | string | |
| email | string | |
| company | string | nullable |
| notes | text | nullable |

### Project
| Field | Type | Notes |
|---|---|---|
| name | string | |
| customer_id | foreign key | links to Customer |
| github_repo | string | e.g. `org/repo-name` |
| stack | string | e.g. Laravel, Next.js |
| status | enum | active / paused / complete |
| description | text | nullable |

### Task
| Field | Type | Notes |
|---|---|---|
| project_id | foreign key | links to Project |
| title | string | |
| description | text | |
| type | enum | bug / feature / change |
| priority | enum | low / medium / high |
| status | enum | backlog / in-progress / done |
| source | enum | manual / ai-chat |
| original_input | text | raw input before structuring |

### Conversation
| Field | Type | Notes |
|---|---|---|
| project_id | foreign key | links to Project |
| user_id | foreign key | links to User |
| type | enum | text / image / voice |
| messages | json | full conversation history |
| final_tasks | json | extracted task objects |
| status | enum | discussing / confirmed |

---

## Key Decisions Log

| # | Decision | Rationale | Date |
|---|---|---|---|
| 1 | GitHub-native (no separate PM tool) | Reduces tool sprawl, keeps source of truth unified | Phase 0 |
| 2 | `PROJECT.md` as agent memory | File-based context is portable, readable, version-controlled | Phase 0 |
| 3 | Laravel as backend framework | PHP ecosystem, strong queue support, team familiarity | Phase 0 |
| 4 | Agents open PRs, never push to main | Human always in the loop for final merge decision | Phase 0 |
| 5 | One agent per project (not shared) | Isolation prevents cross-project context contamination | Phase 0 |
| 6 | Structured prompt engineering over ad-hoc prompts | Consistent, context-rich prompts produce reliable agent output | Phase 0 |
| 7 | Phase 1 = CRUD first, not GitHub integration | Build what's useful from day one; layer complexity later | Phase 0 |
| 8 | Filament v3 for admin panel | Free, open source, Laravel-native, avoids $199 Nova or jQuery-based Backpack | Phase 0 |
| 9 | `laravel/ai` as sole AI package | Replaces multiple separate packages — one unified API for text, agents, voice, images, embeddings | Phase 0 |
| 10 | PostgreSQL over SQLite for production | JSON columns, pgvector support for future semantic search, reliable at scale | Phase 0 |
| 11 | `RemembersConversations` for AI memory | Solves "AI forgets context" — conversations persisted in DB, resumable across sessions | Phase 0 |
