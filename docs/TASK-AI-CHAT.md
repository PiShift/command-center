# 🗣️ Task AI Chat — From Customer Chaos to Clean Tasks

> The conversation-to-tasks pipeline. Customer sends chaos. Founder discusses with AI. Tasks get created.

---

## The Problem

Customers never write clear specs.

- They send screenshots, voice messages, vague texts like "the button is weird"
- The founder is the translator between customer chaos and dev tasks
- That translation currently happens in the founder's head and gets lost
- If raw input is sent to AI without discussion, it gets interpreted wrongly

A customer says: "The thing where you add items doesn't work right."

Do they mean:
- The form validation is broken?
- The add button doesn't respond?
- Items appear but disappear on refresh?
- The UI looks off on mobile?

Without discussion, even AI will guess wrong.

---

## The Solution: Task AI Chat

A conversation-to-tasks pipeline with human confirmation. **NOT an auto-parser.**

The key insight: AI interprets first, founder discusses and corrects, then confirms. No task is created without founder sign-off.

---

## Flow

```
Customer sends feedback (screenshot, voice, vague text)
  → Founder opens PiShift (web or mobile app)
    → Opens "Task AI Chat" for that specific project
      → Pastes screenshot / records voice / types text
        → AI interprets: "Here's what I understood..."
          → Founder: "No, they meant X not Y"
            → AI adjusts: "Got it, so the tasks are..."
              → Founder: "Yes, accept"
                → Tasks get created in the project ✅
```

---

## Core Rules

1. **AI NEVER auto-creates tasks from raw input** — it always interprets first and waits
2. **AI always shows interpretation first** and waits for founder confirmation
3. **Discussion continues** until founder explicitly accepts
4. **Everything is logged:** original input, the full discussion, the final tasks
5. **Tasks are always linked to a specific project** — no floating tasks

---

## Input Types

| Type | Examples |
|---|---|
| 📝 Text | Customer messages, emails, chat logs, copy-pasted WhatsApp |
| 📸 Images/Screenshots | UI bugs, design feedback, mockups, annotated screens |
| 🎤 Voice | Recorded voice notes (transcribed then interpreted) |
| 📋 Mixed | Any combination of the above in a single session |

---

## AI Interpretation Process

```
1. Receive raw input (text / image / voice transcript)
2. Identify the type: bug report / feature request / change request / question
3. Extract key information from the input
4. Present structured interpretation to founder:
   "Here's what I understood from this input..."
5. Founder reviews:
   - If incorrect → founder corrects, AI re-interprets
   - If missing context → AI asks a clarifying question
6. Founder confirms interpretation
7. AI generates task(s) with:
   - Title
   - Description
   - Type (bug / feature / chore)
   - Priority suggestion
8. Founder can edit generated tasks before final acceptance
9. Tasks get inserted into the selected project ✅
```

---

## Example Session

**Input:** Customer screenshot of a broken form + voice note: "The client said the form doesn't submit on iPhone"

**AI:** "I see a form submission issue on iOS. Based on the screenshot, the submit button appears disabled after the first tap. My interpretation: this is likely a JavaScript event handling bug on mobile Safari. Proposed task: *Fix: form submit button becomes unresponsive after first tap on iOS Safari*. Is this correct?"

**Founder:** "Not exactly — it submits but the user sees no confirmation, they just tap again thinking it didn't work."

**AI:** "Got it — the issue is missing user feedback after submission, not a submit failure. Revised task: *Fix: add submission confirmation feedback on iOS (button state + success message)*. Correct?"

**Founder:** "Yes, accept."

**Result:** Task created with full context: original screenshot, original voice note transcript, full discussion, final task. Nothing lost.

---

## What Gets Stored

Every Task AI Chat session logs:

- **Original input** — the raw customer feedback (screenshot, transcript, text)
- **Conversation history** — the full back-and-forth between founder and AI
- **Interpretation steps** — what the AI understood and how it changed
- **Final tasks** — the confirmed, structured tasks created
- **Project link** — which project these tasks belong to
- **Timestamp** — when the session happened

---

## Web vs Mobile

| Platform | Phase | Description |
|---|---|---|
| 🌐 Web (Laravel) | Phase 2 | Full Task AI Chat in the Laravel dashboard. Drag-and-drop screenshots, paste text, record voice. Complete conversation interface. |
| 📱 Flutter Mobile | Phase 6 | Same feature, lightweight app. Customer sends screenshot → founder opens app → 30 seconds → tasks created. Same backend API, different frontend. |

---

## Why This Matters

- **10x faster** at translating customer feedback into tasks
- **Nothing gets lost** — original input + discussion + final tasks all logged
- **No bad interpretations** — founder is always in the loop before anything is created
- **AI learns project context** over time for better interpretation
- **Customers stay chaotic** — founder stays organised
- **Audit trail** — months later you can see exactly what the customer said and what was decided

---

## What This Is NOT

- ❌ An auto-parser that creates tasks without confirmation
- ❌ A replacement for talking to customers
- ❌ A full agent engine (that's Phase 4 — AI Layer 2)
- ❌ A GitHub integration feature (tasks sync to GitHub in Phase 3)

Task AI Chat is AI Layer 1: lightweight, conversational, human-confirmed. The full agent engine comes later.
