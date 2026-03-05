# 💡 IDEAS

> Raw ideas parking lot. No filtering, no commitment. Just capture.
> When an idea is ready to build, move it to `ROADMAP.md`.

---

## Captured Ideas

### 🎙️ Voice-to-Task Pipeline
Record a voice note on your phone while walking. Transcribed by Whisper, structured by LLM, and turned into a GitHub issue automatically. No typing required.

### 📸 Screenshot Analysis for Bug Reports
Drag a screenshot into Command Center. Vision model analyses it, identifies the bug or UI issue, and creates a properly formatted GitHub issue with reproduction steps.

### 📊 Agent Performance Scoring
Track each agent's output quality over time: PR acceptance rate, number of revision cycles, issues resolved vs. opened. Score agents and identify which workflows produce the best results.

### 📰 Auto-Generated Weekly Reports Per Project
Every Monday morning, each project gets a summary report: what was completed last week, what's in progress, what's blocked. Sent via email or published to a dashboard. No manual writing.

### 💬 Slack / Discord Integration for Notifications
Receive a Slack or Discord message when:
- An agent opens a PR
- A blocker is detected
- A PR is approved and merged
- A task has been idle for too long

Stay informed without checking the dashboard constantly.

### 📱 Mobile Dashboard
Lightweight mobile view of the War Room. Review PRs and approve or reject from your phone. Designed for quick decisions, not deep work.

### 🔁 Agent Improvement Loops
When a PR is rejected or revised, the feedback gets written back to `PROJECT.md`. The agent learns from the correction. Over time, each agent gets better at the specific patterns of each project.

### 📅 Client-Facing Status Pages
Auto-generated, read-only project status pages for clients. Shows what's in progress, what was recently shipped, and what's coming up. Pulls directly from GitHub. No manual updates.

### 🔗 Cross-Project Dependency Mapping
Detect when a feature in Project A depends on something in Project B. Flag the dependency, prevent the agent from proceeding until the blocker is resolved.

### 🧪 Automated QA Agent
A separate agent role focused on writing tests and running QA — not just implementing features. Agents pair: one implements, one reviews and tests.
