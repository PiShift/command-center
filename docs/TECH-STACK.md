# 🧱 Tech Stack — PiShift Command Center

> The definitive tech stack reference. Every package, every decision, every rationale.

---

## Framework

**Laravel 11** (latest, PHP)

The backbone. Batteries-included, mature ecosystem, excellent queue/job support, and a massive community. PHP everywhere — one language, one mental model.

---

## Admin Panel

**Filament v3** — free, open source, Laravel-native TALL stack

- Tailwind CSS + Alpine.js + Livewire + Laravel
- Built-in CRUD, tables, forms, filters, bulk actions, notifications, dashboard widgets, dark mode
- First-class developer experience with minimal boilerplate

**Why NOT the alternatives:**
| Option | Why We Skipped It |
|---|---|
| Nova | Paid ($199), slower community movement |
| Backpack | jQuery-based, feels dated |
| Orchid | Smaller community, less momentum |
| Custom build | Defeats the purpose — we want to build features, not admin panels |

---

## Frontend

| Layer | Technology | Notes |
|---|---|---|
| Reactivity | Livewire 3 | Comes with Filament — no separate JS framework needed |
| Styling | Tailwind CSS | Comes with Filament |
| Interactivity | Alpine.js | Comes with Filament, lightweight JS for interactions |

**No React. No Vue.** One language (PHP) everywhere, no JS build step headaches, no context switching between ecosystems.

---

## Authentication & Authorization

- **Laravel built-in auth** — session-based auth out of the box
- **Filament Shield** (`bezhansalleh/filament-shield`) — roles & permissions UI built for Filament
- **`spatie/laravel-permission`** — the permission engine under the hood

---

## Database

**PostgreSQL**

- Reliable and battle-tested
- JSON columns for flexible data storage
- Scalable beyond SQLite
- Supports **pgvector** for future semantic search (Phase 4+, via `laravel/ai` embeddings)

---

## Queue & Jobs

- **Redis + Laravel Queues** — for AI API calls, GitHub sync, background tasks
- **Laravel Horizon** — queue monitoring dashboard, job throughput visibility, failed job management

---

## AI Integration — `laravel/ai` (Laravel AI SDK)

> This is the CORE of Command Center.

The official Laravel AI package. Replaces the need for multiple separate AI packages (`openai-php/laravel`, separate transcription packages, etc.) with a single unified API.

### What It Provides

| Capability | Details |
|---|---|
| **Text / Chat** | Unified API for OpenAI, Anthropic, Gemini, xAI, DeepSeek, Mistral, Ollama, and more |
| **Agents** | Built-in Agent interface with tools and structured output |
| **Conversation persistence** | `RemembersConversations` trait — stores conversations in `agent_conversations` table automatically |
| **Structured output** | JSON schema responses — perfect for extracting clean task objects from messy customer feedback |
| **Image analysis** | For interpreting customer screenshots (bug reports, design feedback, mockups) |
| **Audio transcription (STT)** | Voice → text via OpenAI Whisper, ElevenLabs, Mistral |
| **Text-to-speech (TTS)** | Via OpenAI, ElevenLabs |
| **Embeddings** | Vector embeddings for semantic search (Phase 4+ via pgvector) |
| **Reranking** | AI-powered result reranking |

### How It Maps to Our Features

| Feature | laravel/ai Capability Used |
|---|---|
| Task AI Chat (Phase 2) | Agent + `RemembersConversations` + Structured Output + Transcription + Image Analysis |
| Agent Engine (Phase 4) | Full agents with custom tools, PROJECT.md reading, structured code task generation |
| Conversational memory | `RemembersConversations` persists and resumes conversations — solves "AI forgets context" |

### Two AI Layers

**AI Layer 1 — Phase 2 (Lightweight):**
LLM API calls via `laravel/ai` for Task AI Chat. Interpret text, images, and voice. Discuss with the founder, confirm, and store tasks. Uses Agent + RemembersConversations + Structured Output.

**AI Layer 2 — Phase 4 (Full Agent Engine):**
Context-aware prompt engineering system. Reads PROJECT.md, understands codebase, generates code, opens PRs. Uses full Agent capabilities with custom tools.

---

## File Storage

**S3 or DigitalOcean Spaces**

- Customer screenshots
- Voice recordings
- Uploaded files

Managed via `spatie/laravel-medialibrary`.

---

## GitHub Integration

- **Laravel HTTP client + GitHub REST API**
- Repos, issues, and PRs sync
- Bidirectional: Tasks ↔ Issues, PRs = progress updates

---

## Real-time

**Laravel Reverb** — WebSockets for War Room live updates (Phase 5)

Native Laravel WebSocket server. No third-party dependencies like Pusher required.

---

## API Auth (for Mobile)

**Laravel Sanctum** — API token auth for Flutter mobile app (Phase 6)

---

## Mobile (Phase 6)

**Flutter** — lightweight mobile client, same backend API

Primary use case: Quick Task AI Chat on the go. Customer sends feedback → open app → 30 seconds → tasks created and stored.

---

## Deployment

**Forge / Coolify / Docker** — founder's preference. All standard Laravel deployment options supported.

---

## Complete Package List

| Package | Purpose |
|---|---|
| `laravel/ai` | THE AI package. Text, agents, conversations, images, voice, transcription, embeddings. Replaces `openai-php/laravel` and all separate AI packages. |
| `filament/filament` | Admin panel, CRUD, dashboard |
| `bezhansalleh/filament-shield` | Roles & permissions UI |
| `spatie/laravel-permission` | Under the hood for Shield |
| `spatie/laravel-activitylog` | Log everything — who did what, when |
| `spatie/laravel-medialibrary` | File uploads (screenshots, voice recordings) |
| `spatie/laravel-data` | Clean DTOs for API responses |
| `laravel/reverb` | WebSockets for real-time (Phase 5) |
| `laravel/horizon` | Queue monitoring dashboard |
| `laravel/sanctum` | API auth for Flutter app (Phase 6) |

---

## Why This Stack Works

1. **One language** — PHP everywhere, no React/Vue build step headaches
2. **Filament does the boring work** — CRUD, tables, forms, auth, navigation, all covered
3. **`laravel/ai` handles ALL AI needs** — one package, unified API, conversation memory built in
4. **Focus on the smart parts** — Task AI Chat logic, agent workflows, GitHub sync
5. **Scales naturally** — Phase 1 is Filament CRUD → Phase 2 adds AI via `laravel/ai` → Phase 6 adds Flutter via Sanctum API
6. **No context switching** — Laravel + Livewire + Tailwind + `laravel/ai`, that's it
