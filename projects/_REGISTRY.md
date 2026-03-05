# 📂 PROJECT REGISTRY

> Master list of all projects managed by PiShift Command Center.
> Keep this up to date. The War Room dashboard reads from this list.

---

## How to Add a Project

1. Copy a row from the template below
2. Fill in all fields
3. Create a `PROJECT.md` in the project's repo using [`PROJECT-TEMPLATE.md`](../PROJECT-TEMPLATE.md)
4. Assign an agent or mark as "Unassigned"
5. Commit the update to this file

---

## Active Projects

| # | Project | Repo | Stack | Status | Agent | Last Activity |
|---|---|---|---|---|---|---|
| — | _Project Name_ | [org/repo](https://github.com/org/repo) | _e.g. Laravel, Vue_ | 🟡 Planned | Unassigned | — |

---

## Status Key

| Emoji | Status |
|---|---|
| 🟢 | Active — agent working |
| 🟡 | Planned — not started |
| 🔴 | Blocked — waiting on input |
| ⚪ | Paused — on hold |
| ✅ | Complete — shipped |

---

## Notes

- Every project in this registry must have a `PROJECT.md` in its own repo
- Agents are assigned per project — one agent per project, no sharing
- "Last Activity" = date of last agent action or human update
- Remove completed projects to an "Archive" section below once shipped
