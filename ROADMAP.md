# 🗺️ ROADMAP

> Phased delivery plan. Each phase builds on the last.

---

## Phase 0 — Documentation & Architecture ✅ *(Complete)*

**Goal:** Define the vision, structure, and rules before writing a single line of app code.

- [x] Write `VISION.md` — why this exists, the north star
- [x] Write `ARCHITECTURE.md` — system design, components, data flow
- [x] Write `ROADMAP.md` — this file
- [x] Write `PROJECT-TEMPLATE.md` — standard template every project must use
- [x] Write `docs/AGENT-WORKFLOW.md` — how agents operate
- [x] Write `docs/PAIN-POINTS.md` — real problems that drive features
- [x] Write `docs/IDEAS.md` — future ideas parking lot
- [x] Create `projects/_REGISTRY.md` — master project list

---

## Phase 1 — Projects & Tasks Management (CRUD) *(Next)*

**Goal:** Create projects, link to GitHub repos, manage tasks. This is the foundation. Useful from day one — no AI required.

- [ ] Laravel project scaffolding and initial setup
- [ ] Basic project creation — name, description, linked GitHub repo
- [ ] Task creation per project — title, description, status
- [ ] Task status updates — Todo / In Progress / Done
- [ ] Project list view — see all projects at a glance
- [ ] Task list view per project — see all tasks for a project
- [ ] Must be fast and easy — minimal clicks to create or update

**Milestone:** Can create a project, add tasks, and update their status — without touching GitHub or AI.

---

## Phase 2 — Quick Task Updates & Input

**Goal:** Fast input methods so updating a task takes seconds, not minutes.

- [ ] Text input → structured task update via LLM
- [ ] Voice summary → AI-assisted task update ("record what I just did")
- [ ] One-click status changes from the task list
- [ ] AI-assisted task summaries — turn a brain dump into a clean task entry
- [ ] Input review step — developer confirms before changes are applied

**Milestone:** Record what you just worked on in under 30 seconds.

---

## Phase 3 — GitHub Sync

**Goal:** Bidirectional sync between Command Center tasks and GitHub activity.

- [ ] GitHub OAuth / Personal Access Token authentication
- [ ] Sync tasks ↔ GitHub Issues (create, update, close)
- [ ] PRs show as progress on linked tasks
- [ ] Auto-status update: when a PR is merged, related task auto-completes
- [ ] Project status derived from repo activity
- [ ] Pull open PRs and issues into the task view

**Milestone:** Task status reflects real GitHub activity — no manual syncing.

---

## Phase 4 — AI Agent Layer

**Goal:** Context-aware prompt engineering system that acts on projects intelligently.

- [ ] `PROJECT.md` parser — read and structure project context
- [ ] Structured prompt builder: project context + task scope + rules + guardrails
- [ ] Agent task runner — given a task, implement and open a PR
- [ ] Context Manager — read before, write after every agent action
- [ ] Agent guardrails — self-validation before any write operation
- [ ] Agent logging — every action logged with timestamp and rationale
- [ ] Task generation from GitHub issues
- [ ] Manual trigger — developer assigns task to agent via Command Center

**Milestone:** Agent reads `PROJECT.md`, implements a task, opens a focused PR.

---

## Phase 5 — War Room Dashboard

**Goal:** Real-time view of all projects, agent status, blockers, and progress. One screen to rule them all.

- [ ] Real-time dashboard built with Livewire or Vue
- [ ] Per-project cards: status, agent state, open PRs, blockers
- [ ] PR review queue — all open PRs across all projects in one place
- [ ] Blocker feed — what's stuck and why
- [ ] Recent activity timeline — what agents did in the last 24h
- [ ] Approve / request changes directly from the dashboard

**Milestone:** Wake up, open dashboard, see everything, take action — without leaving Command Center.

---

## Future / Backlog

See [`docs/IDEAS.md`](docs/IDEAS.md) for the raw ideas list.

Notable future directions:
- Multi-agent orchestration — parallel agents across projects with priority queues
- Slack / Discord integration for notifications
- Client-facing project status reports (auto-generated)
- Agent performance scoring and improvement loops
- Mobile dashboard for reviewing PRs on the go
