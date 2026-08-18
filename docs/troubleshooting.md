# Troubleshooting & Common Issues

Use this guide to diagnose and resolve issues with your package integration.

---

## 1. Node Bridge Offline

### Symptom:
Artisan tests print `CRITICAL: Node Bridge is unreachable`, or dashboard displays `Node Bridge Offline`.

### Causes:
- The Node Bridge process is not running.
- The `RICH_WHATSAPP_BRIDGE_URL` configured in `.env` is incorrect or contains a trailing slash.
- Firewalls on the Node server are blocking incoming requests.

### Resolution:
- Check that the Node service starts successfully and responds to `GET /api/v1/health`.
- Strip any trailing slashes from the configured URL in `.env`.
- Ping the Node server from your Laravel environment:
  ```bash
  curl -I https://your-node-bridge.com/api/v1/health
  ```

---

## 2. Webhook Authentication Failures

### Symptom:
Incoming messages do not appear in the dashboard, or status updates fail. Node Bridge logs `callback failed: HTTP 401 Unauthorized`.

### Causes:
- `RICH_WHATSAPP_CALLBACK_TOKEN` on the Laravel side does not match `CALLBACK_TOKEN` in the Node Bridge `.env`.
- The Node Bridge is sending the wrong token header.

### Resolution:
- Ensure both tokens match exactly.
- Verify the token does not contain whitespace.

---

## 3. Phone Number Normalized Wrongly

### Symptom:
A message fails to send with `INVALID_PHONE` or `invalid phone format`.

### Causes:
- The phone number input starts with a national prefix (e.g. `010...`) but `RICH_WHATSAPP_DEFAULT_COUNTRY_CODE` is not configured or configured wrongly.
- The country code has a leading `+` in `.env` (it should contain digits only).

### Resolution:
- Configure `.env` using digits only:
  ```env
  RICH_WHATSAPP_DEFAULT_COUNTRY_CODE=20 # (no plus sign)
  ```
- Use international format directly on user inputs: `+201012345678`.
