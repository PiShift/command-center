# 💡 IDEAS

> Raw ideas parking lot. No filtering, no commitment. Just capture.
> When an idea is ready to build, move it to `ROADMAP.md`.

---

## Captured Ideas

### 🗣️ Task AI Chat
Conversation-to-tasks pipeline with human confirmation. Customer sends chaos (screenshot, voice, vague text) → AI interprets → founder discusses and corrects → founder confirms → tasks created. AI never auto-creates — always confirm first.

### 📱 Flutter Mobile App for Quick Task Creation
Same Task AI Chat on mobile. Customer sends screenshot → founder opens Flutter app → 30 seconds → tasks created. Optimised for on-the-go task creation, not deep work. Same backend API, lightweight Flutter frontend.

### 🤖 AI Interpretation of Screenshots
Drag a screenshot into Task AI Chat. Vision model analyses it — identifies the bug, UI issue, or design feedback — and presents an interpretation for founder to confirm before any tasks are created.

### 🎙️ Voice-to-Task with Discussion Loop
Record a voice note on your phone. Whisper transcribes it. AI interprets and presents what it understood. Founder discusses and corrects until tasks are confirmed. Not auto-parse — discussion loop required.

### 📋 Log Original Customer Input Alongside Tasks
Every task created through Task AI Chat stores the original customer input (screenshot, voice transcript, raw text) alongside the final task. Full traceability: months later you can see exactly what the customer said.

### 🧠 AI Learns Project Context Over Time
Task AI Chat gets better with each session. As more customer feedback is interpreted for a project, AI builds familiarity with the project's domain, common issues, and terminology. Better interpretations, fewer correction rounds.

### 🎙️ Voice-to-Task Pipeline
*(Superseded by Task AI Chat's voice input with discussion loop — see above. The original idea was full auto-parse, but the confirmed approach requires human discussion and confirmation before tasks are created.)*

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

---

## AI & Memory Ideas

### 🔁 Persistent Agent Memory via RemembersConversations
Use `laravel/ai` `RemembersConversations` trait for persistent agent memory. Conversations stored in `agent_conversations` table — fully resumable across sessions. Founder can pick up mid-conversation with context fully intact.

### 🧩 Structured Output for Clean Task Extraction
Use `laravel/ai` structured output (JSON schema responses) to extract clean, typed task objects from raw customer feedback or brain dumps. No manual parsing, no hallucinated fields — schema-enforced every time.

### 🔍 Semantic Search via Embeddings + pgvector (Phase 4+)
Use `laravel/ai` embeddings + PostgreSQL pgvector extension for semantic task search. "Find tasks similar to this customer complaint" — without exact keyword matching. Future-proof the database from day one by using PostgreSQL.

### 📊 Agent Performance Tracking via Activity Logs
Use `spatie/laravel-activitylog` to track every agent action. Correlate activity logs with task outcomes — which prompt patterns produce accepted PRs vs. revision cycles? Identify what works and encode it into the prompt engineering system.
