# Laravel Events Reference

The package fires standard Laravel events during session changes and message status updates. You can attach listeners inside your application's `EventServiceProvider`.

---

## Messaging Events

### `WhatsAppMessageReceived`
Fired when an incoming WhatsApp message is parsed and saved to the database.

**Payload:**
- `message`: `RichWhatsAppMessage`
- `conversation`: `RichWhatsAppConversation`
- `payload`: `array` (raw callback body)

---

### `WhatsAppMessageSent`
Fired when an outgoing message is accepted by the WhatsApp network.

**Payload:**
- `message`: `RichWhatsAppMessage`
- `requestId`: `string`
- `whatsappMessageId`: `string`

---

### `WhatsAppMessageDelivered`
Fired when a message is successfully delivered to the recipient's device.

**Payload:**
- `message`: `RichWhatsAppMessage`
- `requestId`: `string`

---

### `WhatsAppMessageRead`
Fired when a message is read by the recipient (blue ticks).

**Payload:**
- `message`: `RichWhatsAppMessage`
- `requestId`: `string`

---

### `WhatsAppMessageFailed`
Fired when a message send attempt fails permanently.

**Payload:**
- `message`: `RichWhatsAppMessage`
- `requestId`: `string`
- `reason`: `string|null`

---

## Session Events

### `WhatsAppSessionConnected`
Fired when the WhatsApp account is successfully linked and online.

**Payload:**
- `status`: `SessionStatus`
- `phone`: `string|null`

---

### `WhatsAppSessionDisconnected`
Fired when the WhatsApp account loses its connection (temporary).

**Payload:**
- `status`: `SessionStatus`
- `reason`: `string|null`

---

### `WhatsAppSessionQrRequired`
Fired when a new QR code is ready for scanning.

**Payload:**
- `status`: `SessionStatus`
- `qr`: `string|null`

---

### `WhatsAppSessionStatusChanged`
Fired during any state machine status transitions.

**Payload:**
- `previous`: `string|null`
- `current`: `string`
- `phone`: `string|null`
