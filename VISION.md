# 🧭 VISION

> The big picture. Why this exists, what it's trying to become.

---

## The Problem

One developer. 10+ client projects. AI tools that are powerful but dumb.

Here's what actually happens every day:

- **No memory, no context.** Every AI session starts from zero. What was discussed, confirmed, and decided last week? Gone. The agent has no idea.
- **Agents repeat mistakes.** Without context, the same errors get made on the same projects. There's no learning, no improvement, no accountability.
- **Forgotten decisions.** "Didn't we already decide on X?" Yes — three sessions ago. Now it's been undone.
- **Wrong repos, wrong data, hallucinations.** Agents write to the wrong place, use stale data, make up details. No guardrails.
- **Context switching kills productivity.** Jumping between 10 projects, each with their own tools, tabs, and threads, means nothing gets deep focus.
- **PM tools need more maintenance than the projects.** Every tool — Notion, Linear, Jira — becomes another thing to maintain, another source of truth to keep updated. It all breaks down.
- **Agents assume instead of asking.** When something is ambiguous, they guess. That guess is usually wrong and costs hours to fix.

The tools exist. The intelligence exists. What's missing is the system that ties it all together.

---

## The Dream

A CEO-mode workflow for a solo developer running a software company.

**You** are the architect, reviewer, and final decision-maker. You are not in the weeds. You are not copy-pasting context into every AI chat window. You are not maintaining a spreadsheet of "what did I decide on this project last Tuesday."

**AI agents** are your employees. One per project. Each one has full context on their project, knows the decisions that were made, understands the current state, and works autonomously within clear guardrails.

**GitHub** is the backbone. Repos are projects. Issues are tasks. PRs are work delivered. The GitHub API is the data layer. There is no separate PM tool — GitHub IS the PM tool.

**The War Room** is one screen showing everything: all projects, all agents, all status, all blockers. You wake up, check the dashboard, review what your agents did overnight, approve or redirect, and move on.

Nothing gets lost. Nothing gets repeated. Nothing gets assumed.

---

## Core Principles

### 🧠 Agent Memory
Each project has a `PROJECT.md` file — the agent's brain. Conversations, confirmations, decisions, and context are logged here. Agents NEVER work blind. They read this file before doing anything. They write to it after completing anything. They don't repeat mistakes.

### 🎯 GitHub-Native
No separate Project Management (PM) tool. GitHub is the single source of truth.
- Issues = Tasks
- PRs = Work Done
- Repos = Projects
- GitHub API = Data Layer

Everything flows through GitHub. If it's not in GitHub, it doesn't exist.

### 🗣️ Input Flexibility
Ideas and tasks come in all forms — voice notes on a walk, a screenshot of a bug, a brain dump at 2am, a text description of a feature request. All of it gets captured, structured, and turned into actionable GitHub issues without manual reformatting.

### 📺 War Room Dashboard
One screen. All projects. Real-time. You see:
- What each agent is working on right now
- What's blocked and why
- What PRs are waiting for review
- What was completed since you last checked

No tab switching. No digging through Slack. One dashboard, full picture.

### 🗣️ Task AI Chat
Customer feedback comes in as chaos — screenshots, voice notes, vague texts. Task AI Chat is a conversation-to-tasks pipeline that turns that chaos into structured, actionable tasks.

How it works:
- Founder pastes screenshot / records voice / types customer message
- AI interprets: "Here's what I understood..."
- Founder discusses and corrects: "No, they meant X not Y"
- AI adjusts until founder is satisfied
- Founder confirms → tasks are created

**AI NEVER auto-creates tasks** — the founder is always in the loop before anything is committed. Everything is logged: the original customer input, the full discussion, and the final tasks. Available on web first (Phase 2), then Flutter mobile app (Phase 6).

### 🤖 Agent Autonomy with Guardrails
Agents are autonomous but not unsupervised. They:
- Open PRs — never push directly to `main`
- Ask for clarification when something is ambiguous — never assume
- Validate before writing — never hallucinate or use stale data
- Log decisions and progress — never work silently
- Escalate blockers immediately — never spin in circles

---

## North Star

> "I should be able to wake up, look at my dashboard, see what my agents did overnight, review their PRs, approve or redirect, and move on to the next decision."

That's it. That's the whole product.
