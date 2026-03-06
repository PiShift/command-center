# 🏗️ ARCHITECTURE

> System design, component breakdown, and key technical decisions.

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
- Maintains a list of all managed projects
- Stores repo URL, stack, status, assigned agent, and last activity
- Source of truth for the War Room dashboard
- Backed by `projects/_REGISTRY.md` and local database

### 🤖 Agent Engine
- One agent instance per project
- Reads `PROJECT.md` to load full context before every task
- Executes tasks: creates branches, writes code, opens PRs
- Reports back to Command Center with progress and blockers
- Never acts without reading context first

### 🧠 Context Manager
- Reads and writes `PROJECT.md` for each project
- Structures and stores:
  - Confirmed decisions
  - Completed work
  - Current task state
  - Open questions
- Ensures agents never work from a blank slate
- Called before and after every agent action

### 🎯 Task Generator
- LLM-powered conversion of raw input → structured GitHub issues
- Accepts: free text, voice transcription, screenshots, brain dumps
- Outputs: GitHub issues with title, description, labels, and priority
- Uses project context to make tasks accurate and relevant

### 📺 War Room Dashboard
- Real-time view of all active projects
- Shows: agent status, open PRs, blocked items, recent activity
- Built with Livewire (real-time updates without full-page refresh) or Vue
- The primary daily-driver interface for the developer

### 🗣️ Input Parser
- Captures and normalises diverse input types:
  - **Voice notes** → transcribed and structured via LLM
  - **Screenshots** → analysed for bugs, UI feedback, or feature ideas
  - **Text / brain dumps** → parsed and broken into discrete tasks
- All inputs are converted to GitHub issues before any agent acts on them

---

## Tech Stack

| Component | Technology | Rationale |
|---|---|---|
| Backend framework | Laravel (PHP) | Mature, batteries-included, excellent queue/job support |
| GitHub integration | GitHub REST API v3 | Official API, full repo/issue/PR control |
| LLM provider | OpenAI GPT-4 / Anthropic Claude | Best in class for code and reasoning tasks |
| Frontend | Livewire or Vue 3 | TBD — Livewire preferred for simplicity |
| Real-time | Laravel Broadcasting + Reverb | Native Laravel real-time layer |
| Queue | Redis | Fast, reliable job queue for agent tasks |
| Database | SQLite (dev) / PostgreSQL (prod) | Lightweight local dev, robust production |
| Voice input | Whisper API (OpenAI) | Accurate transcription, same API key |
| Image input | GPT-4 Vision / Claude Vision | Screenshot and image analysis |

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
