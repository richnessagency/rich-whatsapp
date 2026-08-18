# Connecting WhatsApp (QR Scan Flow)

Connecting a WhatsApp account to the Laravel application is a simple one-time process.

## Connection Flow Diagram

```text
User visits /whatsapp/connect
        │
        ▼
Laravel calls session/start on the Node Bridge
        │
        ▼
Node Bridge initializes Baileys socket & generates QR
        │
        ▼
Laravel gets QR PNG Data URL
        │
        ▼
Browser displays QR Code card
        │
        ▼
User scans QR with WhatsApp Mobile App
        │
        ▼
Browser polls status every 3 seconds
        │
        ▼
Connected! Dashboard redirects to Chat view.
```

---

## Instructions

1. Ensure the Node Bridge service is running and configured correctly.
2. In your browser, navigate to:
   ```text
   https://your-laravel-site.com/whatsapp/connect
   ```
3. Open WhatsApp on your phone:
   - Tap **Menu** (Android) or **Settings** (iOS).
   - Select **Linked Devices**.
   - Tap on **Link a Device**.
4. Scan the QR code displayed on the screen.
5. The page will automatically detect the scan, transition to `Connected`, and redirect you to the main dashboard.

---

## Session Persistence

The WhatsApp session itself is stored and maintained **only by the standalone Node Bridge** (in its filesystem `storage/sessions/` directory). 

Laravel does **not** store any session credentials or login files. If you restart the Laravel application, or the Node Bridge service itself, the Node Bridge will automatically restore the socket connection using those stored credentials without requiring a new QR scan.
