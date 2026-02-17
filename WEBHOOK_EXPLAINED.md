# Understanding WhatsApp Webhooks & Your Connection Error

## Your Question: "What is this webhook and how does it work?"

## The Big Picture

Your UltraMsg instance has **TWO completely separate functions**:

### 1. **SENDING Messages** (Outbound)
- **What**: Your apps call UltraMsg API to SEND WhatsApp messages
- **Uses**: Instance ID + Token (credentials)
- **Direction**: Your App → UltraMsg → WhatsApp User
- **Can be shared**: ✅ YES - Multiple apps can use same credentials
- **Example**: Abiy Tsom sends daily reminders, Donate site sends receipts

```
┌─────────────────┐
│  Abiy Tsom App  │ ───┐
└─────────────────┘    │
                       │  Both use same
┌─────────────────┐    │  Instance ID + Token
│  Donate App     │ ───┤
└─────────────────┘    │
                       ▼
              ┌─────────────────┐
              │ UltraMsg API    │
              │ (Your Instance) │
              └─────────────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  WhatsApp User  │
              └─────────────────┘
```

### 2. **RECEIVING Messages** (Inbound)
- **What**: When someone replies to your WhatsApp number, UltraMsg forwards it to your webhook URL
- **Uses**: Webhook URL (only ONE per instance)
- **Direction**: WhatsApp User → UltraMsg → Your Webhook URL
- **Can be shared**: ⚠️ NO - Only ONE webhook URL per instance
- **Current**: `https://donate.abuneteklehaymanot.org/webhooks/ultramsg.php`

```
┌─────────────────┐
│  WhatsApp User  │
│  (sends reply)  │
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ UltraMsg API    │
│ (Your Instance) │
└─────────────────┘
        │
        │ Webhook URL (only ONE!)
        ▼
┌──────────────────────────────────────┐
│ https://donate.abuneteklehaymanot.   │
│ org/webhooks/ultramsg.php            │
│ (Currently handles ALL incoming)     │
└──────────────────────────────────────┘
```

## Your Current Setup

Right now:
- ✅ **Donate app** has webhook configured: `https://donate.abuneteklehaymanot.org/webhooks/ultramsg.php`
- ✅ **Abiy Tsom app** can SEND messages (uses same credentials)
- ❌ **Abiy Tsom app** does NOT receive messages (webhook goes to Donate app)

## If You Change the Webhook URL

### Scenario: You change webhook to `https://abiytsom.abuneteklehaymanot.org/webhooks/ultramsg.php`

**What happens:**
- ✅ Abiy Tsom app RECEIVES incoming messages
- ❌ Donate app STOPS receiving messages (webhook changed)
- ✅ Both apps can still SEND messages (credentials unchanged)

## Solutions

### Option 1: Create a Webhook Router (RECOMMENDED)

Create ONE webhook endpoint that routes messages to both apps:

```php
// https://yourdomain.com/webhooks/ultramsg-router.php

<?php
// Receive webhook from UltraMsg
$data = json_decode(file_get_contents('php://input'), true);

// Forward to Donate app
file_get_contents('https://donate.abuneteklehaymanot.org/webhooks/ultramsg.php?' . http_build_query($data));

// Forward to Abiy Tsom app
file_get_contents('https://abiytsom.abuneteklehaymanot.org/webhooks/ultramsg.php?' . http_build_query($data));

// Return success
echo json_encode(['status' => 'ok']);
```

Then set UltraMsg webhook to: `https://yourdomain.com/webhooks/ultramsg-router.php`

### Option 2: Don't Use Webhooks for Abiy Tsom

If Abiy Tsom only SENDS daily reminders and doesn't need to RECEIVE replies:
- ⚠️ **Don't change the webhook URL**
- Keep it pointing to Donate app
- Abiy Tsom will continue sending messages fine

### Option 3: Get a Second UltraMsg Instance

If you need separate webhook handling:
- Get another UltraMsg instance ($39/month)
- Abiy Tsom uses Instance A (has its own webhook)
- Donate uses Instance B (has its own webhook)
- ⚠️ Each sends from a different WhatsApp number

## About Your Connection Error

The "Connection error" you're seeing is **NOT related to webhooks**.

It's happening when you try to **SEND a test message**. Possible causes:

### 1. Network/Firewall Issue
Your local development server cannot reach `https://api.ultramsg.com`

**Fix**: Check if you can access UltraMsg from your server:

```powershell
# Test from PowerShell
Invoke-WebRequest -Uri "https://api.ultramsg.com"
```

### 2. Wrong Credentials
Instance ID or Token is incorrect

**Fix**: Double-check your credentials in UltraMsg dashboard

### 3. XAMPP/Local Environment
XAMPP on Windows sometimes has SSL/certificate issues

**Fix**: Try from your production server instead

### 4. Check Laravel Logs

Look in `storage/logs/laravel.log` for detailed error:

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

The improved error handling I just added will now show you the EXACT error message instead of generic "Connection error".

## What Does Abiy Tsom Need?

Let me clarify what Abiy Tsom's WhatsApp feature does:

### Current Implementation:
- ✅ Sends daily reminders to members
- ✅ Members opt-in during registration
- ✅ Members choose their reminder time
- ✅ Cron job sends links each day

### Does it need webhooks?
**Probably NOT** - unless you want:
- Members to reply "STOP" to unsubscribe
- Two-way conversation with members
- Members to send commands via WhatsApp

### If you DON'T need incoming messages:
- ⚠️ **Leave webhook URL unchanged**
- Keep it pointing to `https://donate.abuneteklehaymanot.org/webhooks/ultramsg.php`
- Abiy Tsom will work perfectly for sending reminders
- Donate app continues to receive its webhooks
- **You won't lose Donate app's WhatsApp feature!**

## Summary

| Feature | Can Share Across Apps? | Current Status |
|---------|----------------------|----------------|
| **Instance ID** | ✅ YES | Shared between Abiy Tsom + Donate |
| **API Token** | ✅ YES | Shared between Abiy Tsom + Donate |
| **Sending Messages** | ✅ YES | Both apps can send |
| **Webhook URL** | ❌ NO (only one) | Points to Donate app |
| **Receiving Messages** | ❌ NO | Only Donate app receives |

## Recommendation

**For Abiy Tsom daily reminders:**
1. ✅ Use the same Instance ID + Token (you already have)
2. ⚠️ **Don't configure webhook settings** in Abiy Tsom admin
3. Leave webhook URL pointing to Donate app
4. Abiy Tsom will send reminders perfectly
5. Donate app continues to receive incoming messages
6. Everyone is happy!

**The webhook settings page I built is optional.** You only need it if you plan to:
- Handle incoming messages in Abiy Tsom
- OR create a webhook router (Option 1 above)
- OR get a second UltraMsg instance (Option 3 above)

## Next Steps to Fix Your Connection Error

1. Check your actual error by opening the admin page in Chrome
2. Open Developer Tools (F12)
3. Go to Console tab
4. Click "Send Test Message"
5. Look at the console for the actual error message
6. The improved error handling will now show you exactly what's wrong

Send me the error message you see, and I'll help you fix it!
