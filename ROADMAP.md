# 🗺️ ROADMAP

> Phased delivery plan. Each phase builds on the last.

---

## Phase 0 — Documentation & Architecture ✅ *(Current)*

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

## Phase 1 — GitHub Integration

**Goal:** Connect to GitHub API. See all projects. Sync issues and PRs.

- [ ] Laravel project scaffolding and initial setup
- [ ] GitHub OAuth / Personal Access Token authentication
- [ ] List all repos for authenticated user/org
- [ ] Sync issues and PRs per repo into local database
- [ ] Build Project Registry — list of managed projects with metadata
- [ ] Basic CLI or simple UI to view projects and their GitHub status

**Milestone:** Can see all projects and their current GitHub state from Command Center.

---

## Phase 2 — Agent Engine MVP

**Goal:** An agent that can read context and take action on a project.

- [ ] `PROJECT.md` parser — read and structure project context
- [ ] Agent task runner — given an issue, implement and open a PR
- [ ] Context Manager — read before, write after every agent action
- [ ] Basic guardrails — agent validation before any write operation
- [ ] Agent logging — every action logged with timestamp and rationale
- [ ] Manual trigger — developer assigns issue to agent via Command Center

**Milestone:** Agent reads `PROJECT.md`, implements a GitHub issue, opens a PR.

---

## Phase 3 — War Room Dashboard

**Goal:** One screen showing the full picture across all projects.

- [ ] Real-time dashboard built with Livewire or Vue
- [ ] Per-project cards: status, agent state, open PRs, blockers
- [ ] PR review queue — all open PRs across all projects in one place
- [ ] Blocker feed — what's stuck and why
- [ ] Recent activity timeline — what agents did in the last 24h
- [ ] Approve / request changes directly from the dashboard

**Milestone:** Wake up, open dashboard, see everything, take action — without leaving Command Center.

---

## Phase 4 — Input Parser

**Goal:** Any input format → structured GitHub issue.

- [ ] Text input → GitHub issue via LLM
- [ ] Voice note upload → Whisper transcription → LLM structuring → issue
- [ ] Screenshot upload → Vision model analysis → bug report or feature issue
- [ ] Brain dump mode — free-form text split into multiple issues
- [ ] Input review step — developer confirms before issues are created

**Milestone:** Brain dump a page of thoughts in 2 minutes, get 10 structured GitHub issues.

---

## Phase 5 — Multi-Agent Orchestration

**Goal:** Multiple agents working in parallel across projects, with priority management.

- [ ] Agent priority queue — what gets worked on first
- [ ] Parallel execution — multiple agents running concurrently via Redis queues
- [ ] Cross-project dependency detection — flag when project A blocks project B
- [ ] Agent performance tracking — completion rate, error rate, PR acceptance rate
- [ ] Auto-scheduling — agents pick up work based on priority without manual assignment
- [ ] Nightly runs — agents work autonomously overnight within defined guardrails

**Milestone:** Go to sleep. Wake up to completed PRs across multiple projects.

---

## Future / Backlog

See [`docs/IDEAS.md`](docs/IDEAS.md) for the raw ideas list.

Notable future directions:
- Slack / Discord integration for notifications
- Client-facing project status reports (auto-generated)
- Agent performance scoring and improvement loops
- Mobile dashboard for reviewing PRs on the go
