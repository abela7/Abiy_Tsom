# Abiy Tsom — Agent Memory

## Learned User Preferences

- Build everything in English first; use translation keys (e.g. `__('admin.inventory.stock_levels.title')`). Amharic and Tigrinya stay as placeholders until English is stable.
- Mobile-first design for all member-facing pages and new features (same pattern as existing pages).
- Build incrementally: ship one subsection at a time and finish it fully before moving on. Stop and wait for PM review after commits.
- Do not add new packages, UI pages, or extras unless explicitly approved.
- On local setup: when manual steps are required (DB creation, config, etc.), ask the user to do them.

## Learned Workspace Facts

- Brand colors: Primary Blue `#0a6286`, Primary Gold `#e2ca18`.
- Tech stack: Laravel, Blade, Tailwind v4, Alpine.js, MySQL. Always use Tailwind v4 syntax.
