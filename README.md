# 🎯 PiShift Command Center

> AI-powered project orchestration platform — where one developer runs an entire software company.

---

## What Is This

PiShift Command Center is the control hub for running multiple client software projects using AI agents. It gives one developer the leverage of an entire engineering team — with GitHub as the backbone, AI agents as autonomous workers, and a single War Room dashboard to see everything at once.

No more context switching. No more forgotten decisions. No more agents working blind.

---

## Why

Managing 10+ client projects as a solo developer is chaos:
- AI tools are powerful but have no memory or context
- Every project management tool needs more maintenance than the projects
- Context switching kills productivity
- Agents repeat mistakes, forget decisions, write to wrong repos

Command Center fixes all of that. See [`docs/PAIN-POINTS.md`](docs/PAIN-POINTS.md) for the full breakdown.

---

## How It Works

```
You
 │
 ▼
Command Center (Laravel backend)
 │
 ├─▶ Agent per project
 │       │
 │       ├─▶ Reads PROJECT.md (full context)
 │       ├─▶ Opens issues / PRs on GitHub
 │       └─▶ Never pushes to main
 │
 └─▶ GitHub (repos / issues / PRs = source of truth)
```

You stay in the CEO role — reviewing, approving, redirecting. Agents do the execution.

---

## Repo Structure

| Path | Purpose |
|---|---|
| `README.md` | This file — project overview |
| `VISION.md` | The big picture and north star |
| `ARCHITECTURE.md` | System design and tech decisions |
| `ROADMAP.md` | Phased delivery plan |
| `PROJECT-TEMPLATE.md` | Standard template for every managed project |
| `docs/` | Supporting documentation (ideas, pain points, agent workflow) |
| `projects/` | Per-project context files and registry |
| `app/` | Laravel application code |

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel (PHP) |
| GitHub Integration | GitHub REST API |
| AI / LLM | OpenAI API, Anthropic Claude |
| Frontend | Livewire or Vue (TBD) |
| Queue | Redis |
| Database | SQLite (local) / PostgreSQL (production) |
| Dashboard | War Room — real-time project overview |

---

## Status

**Phase 0 — Documentation & Architecture**

Defining the vision, system design, and project template before writing a single line of application code. See [`ROADMAP.md`](ROADMAP.md) for what comes next.
