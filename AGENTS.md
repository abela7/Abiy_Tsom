# Abiy Tsom — Agent Memory

## Learned User Preferences

- Build everything in English first; use translation keys (e.g. `__('admin.inventory.stock_levels.title')`). Amharic and Tigrinya stay as placeholders until English is stable.
- Mobile-first design for all member-facing pages and new features (same pattern as existing pages).
- Build incrementally: ship one subsection at a time and finish it fully before moving on. Prefer waiting for PM review after commits unless the user explicitly asks to commit and push to `origin/main` to deploy.
- Do not add new packages, UI pages, or extras unless explicitly approved.
- On local setup: when manual steps are required (DB creation, config, etc.), ask the user to do them.
- Special fast-day experiences (Good Friday, Fasika, etc.) belong inside `day.blade.php` as conditional blocks driven by controller flags (`$isGoodFriday`, `$isFasika`), not as new routes, standalone Blade pages, or separate member controllers.
- Do not push to `main`/production or deploy without explicit user approval; clear requests such as “commit and push”, “push to main”, or “push it” count as approval for that push. A request only to view or share a link is not permission to push or ship.

## Learned Workspace Facts

- Brand colors: Primary Blue `#0a6286`, Primary Gold `#e2ca18`.
- Tech stack: Laravel, Blade, Tailwind v4, Alpine.js, MySQL. Always use Tailwind v4 syntax.
- Member special-day UI: `HomeController::renderDayView()` sets flags such as `$isGoodFriday` and `$isFasika`; `day.blade.php` gates styling and celebration sections with `@if($isGoodFriday ?? false)` / `@if($isFasika ?? false)` while the normal daily content still renders underneath.
- `DailyContent` is keyed by day number through the last fasting day of the season; the app may include a Day 56 row for Easter. `$isFasika` follows the configured Easter date (`config('app.easter_date')` / `EASTER_DATE` in `.env`) and related controller logic.
- On `$isFasika`, member day, admin preview, and public share force Amharic locale and DB translations; the member layout hides the header language switcher; the Himamat FAQ block in `himamat-linked-sections` is omitted; the closing gratitude doxology uses the `fasika_gratitude_doxology` copy in `lang/am` and `lang/en` without an extra heading.
- Production Abiy Tsom app: `abiytsom.abuneteklehaymanot.org`; deploy over SSH from that app directory with `git pull origin main` (if the server repo has diverged from `origin/main`, `git reset --hard origin/main` then pull again).
- Production cPanel cron lines for this app that ran `schedule:run`, `reminders:send-whatsapp --queue`, and `queue:work ... whatsapp-reminders` were removed; Laravel-scheduled jobs from `routes/console.php` (e.g. writer and email reminders) and WhatsApp reminder dispatch therefore do not run from cron unless comparable cron entries are added back.

## Cursor Cloud specific instructions

### Services overview

| Service | How to start | Port |
|---------|-------------|------|
| Laravel dev server | `php artisan serve --host=0.0.0.0 --port=8000` | 8000 |
| Vite dev server (HMR) | `npm run dev` | 5173 |
| Both + queue + logs | `composer dev` (uses `concurrently`) | 8000 + 5173 |

### Key development commands

See `composer.json` scripts section for canonical commands: `composer dev`, `composer test`, `composer setup`.

- **Lint:** `vendor/bin/pint --test` (check) or `vendor/bin/pint` (fix)
- **Tests:** `php artisan test` (uses SQLite in-memory, no MySQL needed)
- **Build frontend:** `npm run build`

### Non-obvious caveats

- **MySQL socket permissions:** After starting MySQL with `sudo mysqld_safe`, the `/var/run/mysqld/` directory may have restrictive permissions (0700). Run `sudo chmod 755 /var/run/mysqld` so the non-root user can connect via socket.
- **Migration ordering bug:** Migration `2026_02_17_000003_add_whatsapp_language_to_members_table.php` references column `whatsapp_last_sent_date` (via `->after()`), which is only created in the later migration `2026_02_19_000002_add_whatsapp_reminder_columns_to_members_table.php`. Work around this by running the later migration first: `php artisan migrate --path=database/migrations/2026_02_19_000002_add_whatsapp_reminder_columns_to_members_table.php --force` then `php artisan migrate --force`.
- **Default admin credentials:** `admin@abiy-tsom.com` / `password` (created by `php artisan db:seed`).
- **Default locale is Amharic:** `.env.example` sets `APP_LOCALE=am`. The admin and member UIs display in Amharic by default.
- **Pre-existing test failures:** 13 tests currently fail (WhatsApp admin 403s, member auth 401s, and an undefined variable in `SendWhatsAppReminders`). These are known codebase issues, not environment problems.
