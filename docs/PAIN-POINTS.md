# 😤 PAIN POINTS

> Real frustrations that become features. Every entry here drives a design decision.

---

## The Real Problems

### 😤 "AI tools repeat the same mistake without self-awareness"
**Problem:** An agent makes an error on Monday. By Thursday it makes the exact same error because it has no memory of what happened. There is no learning, no improvement, no awareness of past failures.

**Feature this drives:** Agent Memory (`PROJECT.md`). Every mistake gets logged. Agents read context before acting. No repeating history.

---

### 🧠 "I forget what we discussed and confirmed in previous sessions"
**Problem:** Three sessions ago we decided to use PostgreSQL over MySQL, agreed on the API response format, and confirmed the authentication approach. Today a new session starts and none of that exists. We're back to square one. Decisions get relitigated endlessly.

**Feature this drives:** Decisions Log in `PROJECT.md`. Every confirmed decision is written down and read by the agent before it touches anything. Conversations become permanent records.

---

### 🚨 "AI writes to wrong repos, uses wrong data, hallucinates"
**Problem:** Agent writes code for Project A into Project B's repo. Pulls in a library version that doesn't exist. Makes up an API endpoint. Assumes a schema that was never confirmed. All of it costs hours to undo.

**Feature this drives:** Agent guardrails and validation. Agents confirm their working context before writing. They validate repo, stack, and conventions against `PROJECT.md`. They never assume — they ask.

---

### 🔀 "Context switching between 10+ projects kills productivity"
**Problem:** Each project has its own Notion page, GitHub repo, chat thread, and mental context. Switching between them burns 10-15 minutes of mental overhead each time. Deep focus is impossible when you're managing 10 things at once.

**Feature this drives:** War Room Dashboard. One screen, all projects, real-time status. No tab switching. No digging. The mental overhead of context switching is replaced by a single glance.

---

### 🔧 "Every PM tool needs more maintenance than the projects themselves"
**Problem:** Set up Notion. Keep Notion updated. Set up Linear. Keep Linear synced with GitHub. Set up Jira. Explain Jira to everyone. Every tool is another obligation. They drift out of sync. They become inaccurate. They get abandoned.

**Feature this drives:** GitHub-native architecture. GitHub IS the PM tool. Issues are tasks. PRs are deliverables. There is nothing to sync because there is only one source of truth.

---

### ❓ "AI agents assume instead of asking"
**Problem:** Something is ambiguous. Instead of pausing and asking, the agent picks an interpretation and runs with it for two hours. The interpretation is wrong. Two hours of work is thrown away. The agent didn't ask because it was optimizing for completion, not correctness.

**Feature this drives:** Clear escalation rules baked into every agent. When ambiguous: stop, write the question to `PROJECT.md` under "Open Questions", surface it to the developer. Never proceed on an assumption. Never.
