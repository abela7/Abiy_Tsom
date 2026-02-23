# Telegram Bot — Test From Scratch

## Prerequisites

1. **MySQL running** (XAMPP or your setup)
2. **Bot configured** in Admin → Telegram Settings
3. **APP_URL** set in `.env` (must be HTTPS for Web App buttons to work in production)

---

## Step 1: Run migrations

```bash
cd c:\xampp\htdocs\Abiy_Tsom
php artisan migrate
```

---

## Step 2: Ensure config

In `.env`:

```
APP_URL=https://your-domain.com
TELEGRAM_BOT_TOKEN=your_token
TELEGRAM_BOT_USERNAME=your_bot_username
```

For local testing with ngrok:

```
APP_URL=https://abc123.ngrok.io
```

---

## Step 3: Test flow (from scratch)

### A. Unlinked user — Start

1. Open your bot in Telegram
2. Send `/start`
3. You should see:
   - "Welcome to Abiy Tsom. Choose an option:"
   - **[New — Register]** and **[I have an account]** buttons

### B. Test "New"

1. Tap **New — Register**
2. The app opens (WebView or browser)
3. Register with a baptism name
4. You are logged in

### C. Test "I have an account" (link flow)

1. Send `/start` again (or use a fresh Telegram account)
2. Tap **I have an account**
3. Previous message disappears, new message shows:
   - Instructions + **[Open app]** button
4. Tap **Open app** → app opens
5. Go to **Settings** → **Link Telegram**
6. Tap **Generate link**
7. Copy the **6-character code** (e.g. `ABC123`)
8. Go back to Telegram
9. Type the code in the chat (e.g. `ABC123`)
10. Bot replies: "Account linked successfully. Tap a button below:"
11. You see **Home**, **Today**, **My links**, **Help** buttons

### D. Test clean navigation (messages disappear)

1. Send `/start` or `/menu`
2. You see "Quick actions:" with buttons
3. Tap **Menu** → previous message disappears, new message with menu
4. Tap **Help** → previous message disappears, new message with help
5. Tap **My links** → previous message disappears, new message with links
6. Tap **Home** or **Today** → Web App opens (no callback, so message stays until you tap another callback button)

**Note:** Only **callback** buttons (Menu, Help, My links) trigger delete-then-send. **Web App** buttons (Home, Today) open the app — the message stays until you tap a callback button.

### E. Test Web App buttons

1. Tap **Home** → app opens with today’s content
2. Tap **Today** → app opens with today’s daily content (if available)

---

## Expected behavior

| Action | Result |
|--------|--------|
| Tap callback button (Menu, Help, etc.) | Old message deleted, new message sent |
| Tap Web App button (Home, Today) | App opens in WebView |
| Type 6-char code | Account links, menu appears |
| Send `/start` when linked | Menu with Quick actions |

---

## Troubleshooting

| Issue | Check |
|-------|------|
| No response | Webhook URL, bot token |
| "No data shown" after link | APP_URL, migrations run |
| Web App doesn’t open | APP_URL must be HTTPS |
| Code doesn’t work | Code expired (30 min), already used |
