# 🤖 AGENT WORKFLOW

> How AI agents operate per project. These rules are non-negotiable.

---

## Overview

Every agent follows the same workflow, every time, without shortcuts. The workflow is designed to prevent the mistakes that come from agents acting without context, assuming without asking, or working without visibility.

---

## The Workflow

### Step 1 — Receive Task Assignment

The agent receives a task via Command Center. The task is always a GitHub issue — never a vague verbal instruction. The issue has:
- A clear title
- A description with acceptance criteria
- Labels and priority
- A link to the project repo

The agent does not begin work until a valid GitHub issue exists.

---

### Step 2 — Read `PROJECT.md` for Full Context

**Before touching a single file**, the agent reads `PROJECT.md` in full.

This gives the agent:
- The project stack and conventions
- The decisions log — what was decided and why
- The current task state — what's in progress, what's done
- Open questions — things that need human input
- Agent notes — recent history and observations

If `PROJECT.md` is missing or incomplete, the agent flags this as a blocker and does not proceed.

---

### Step 3 — Read Relevant Code Files

The agent reads the code files relevant to the task. It does not guess at structure — it uses the project's structure overview in `PROJECT.md` to navigate efficiently.

The agent reads to understand:
- Existing patterns and conventions
- What already exists that can be reused
- Where new code should live
- What tests already cover this area

---

### Step 4 — Plan Approach and Ask if Ambiguous

The agent prepares a written plan:
- What it intends to implement
- What files it will create or modify
- How it will test the change

**If anything is unclear or ambiguous**, the agent STOPS here. It writes the open question to `PROJECT.md` under "Open Questions" and surfaces it to the developer via Command Center. It does NOT proceed with a guess.

Ambiguity triggers:
- Conflicting requirements
- Missing context about a business rule
- Unclear acceptance criteria
- Risk of breaking something that isn't covered by tests

---

### Step 5 — Implement on a Branch

The agent creates a new branch named for the issue:
```
feature/issue-{number}-{short-description}
fix/issue-{number}-{short-description}
```

The agent implements the change:
- Following project conventions from `PROJECT.md`
- Writing tests where required
- Keeping commits small, clear, and focused
- Never modifying files outside the scope of the task

**The agent never commits to `main`.** Ever.

---

### Step 6 — Open a PR with a Clear Description

The PR description must include:
- **What:** What was implemented
- **Why:** Why this approach was taken (reference the issue)
- **How:** Key implementation details worth noting
- **Testing:** How to verify the change works
- **Closes:** `Closes #N` linking back to the original issue

The PR is opened as a draft if the agent has any uncertainty. It is opened as ready for review only when the agent is confident the work is complete and correct.

---

### Step 7 — Human Reviews and Approves or Requests Changes

The developer reviews the PR in the War Room or directly on GitHub.

Options:
- **Approve and merge** — agent's work is accepted
- **Request changes** — developer adds comments, agent updates the PR
- **Close and reassign** — if the approach was fundamentally wrong, start over

The agent monitors the PR for feedback. When changes are requested, the agent reads all comments, updates `PROJECT.md` with the feedback as a lesson, and addresses each comment explicitly.

---

### Step 8 — Update `PROJECT.md` with Decisions and Progress

After the PR is merged (or closed), the agent updates `PROJECT.md`:
- Marks the task as complete in the Current Tasks table
- Adds any new decisions made during implementation to the Decisions Log
- Adds any lessons learned or patterns observed to Agent Notes
- Clears any resolved Open Questions

`PROJECT.md` is always more complete after an agent interaction than before. This is how context compounds over time.

---

## Rules (Non-Negotiable)

| Rule | Detail |
|---|---|
| 🚫 Never push to `main` | All work goes through PRs. No exceptions. |
| 🚫 Never assume | If it's not in `PROJECT.md` or the issue, ask. |
| ✅ Always read context first | `PROJECT.md` must be read before any file is written. |
| ✅ Log everything | Every decision, completion, and blocker goes in `PROJECT.md`. |
| ✅ Ask when ambiguous | Write the question, surface it, wait for an answer. |
| ✅ Reference the issue | Every PR closes a GitHub issue. No orphaned work. |
| ✅ Keep scope tight | Do the task. Don't refactor the world while you're in there. |
| ✅ Draft PR if uncertain | If there's any doubt, open as draft and explain why. |
