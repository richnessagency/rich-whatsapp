# Security Review & Guarantees

This package enforces strict security policies to protect your application, data, and users.

---

## 1. Secret Isolation
- The `RICH_WHATSAPP_BRIDGE_TOKEN` and `RICH_WHATSAPP_CALLBACK_TOKEN` are secrets that live strictly in your server environment (`.env`).
- They are **never** rendered inside Blade templates or exposed in JavaScript variables.
- The browser communicates solely with the Laravel backend. Laravel then handles authenticated server-to-server requests to the Node Bridge.

---

## 2. Webhook Authentication
- All incoming webhooks from the bridge are verified using a timing-safe string comparison helper (`hash_equals`) on the Bearer token.
- This prevents attackers from guessing the token value using timing attacks.
- CSRF middleware is disabled only on this API endpoint; machine authentication is strictly enforced.

---

## 3. Data Sanitization
- Incoming webhook payloads are validated and sanitized.
- The package records callback events in `rich_whatsapp_callback_events` under a unique `event_id` constraint. This prevents double-delivery or malicious injection attempts of the same webhook payload.
- Message text bodies are escaped inside Blade views using Laravel's safe double-curly brace `{!! !!}` escapes or standard blade `{{ }}` syntax. In the package view templates, standard escaping is used to protect against Cross-Site Scripting (XSS).

---

## 4. Media Path Traversal Protection
- File paths for outbound media are checked before sending.
- Only valid, existing file paths inside standard Laravel directories or uploaded via dashboard uploads are accepted.
- File uploads are validated using Laravel's file validation rules (`mimeType`, `max size`).
- Path traversal tricks (like `../` attempts) are caught early.
- Storage directories of the package are kept inside standard `storage/` frameworks, protecting them from static access over HTTP.
