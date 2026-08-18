# Webhook Callbacks & Security

The Node Bridge forwards WhatsApp activities (status updates, QR scans, incoming messages, and delivery states) to Laravel.

## Callback Security

1. **Authorization Middleware**
   The incoming webhook is protected by `VerifyRichWhatsAppCallbackToken` middleware. It looks for a Bearer token:
   ```http
   Authorization: Bearer {RICH_WHATSAPP_CALLBACK_TOKEN}
   ```
   The token is verified using a constant-time comparison helper (`hash_equals`) to protect against timing attacks.

2. **Strict Sanitization**
   Only expected payload keys are read and stored. Arbitrary metadata, tokens, or credentials inside callback bodies are fully discarded.

---

## Webhook Idempotency

Every callback payload from the Node Bridge contains a unique `event_id`. The package logs each event in the `rich_whatsapp_callback_events` table under a unique constraint. If the bridge retries a webhook due to a temporary HTTP timeout, Laravel detects the duplicate `event_id` and rejects it safely with a successful `200 OK` response without duplicating messages or events.

---

## Status Progression Policy

Delivery statuses (sent, delivered, read) can sometimes arrive out of order due to network variance. The package enforces a strict progression ranking using `MessageStatus::isProgression()`:

```text
queued < submitted < sent < delivered < read
```

A status update is only applied to a message if the incoming status rank is equal to or greater than the current message status rank. For example:
- A message that is already marked `read` cannot regress back to `sent` even if the bridge delivers the `sent` callback late.
- A status of `failed` is terminal and cannot transition to other states.
