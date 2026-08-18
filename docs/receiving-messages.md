# Receiving Messages

The package handles incoming WhatsApp messages automatically via the Callback webhook endpoint.

## Webhook Endpoint
The package registers the API route:
```text
POST /rich-whatsapp/api/callback
```
Ensure your Node Bridge is configured to forward callback payloads to this URL.

---

## Processing Flow

When a WhatsApp message is received by the Node Bridge:

1. The Node Bridge issues a POST request to your Laravel application.
2. The `VerifyRichWhatsAppCallbackToken` middleware validates the callback token in the Authorization header.
3. The `CallbackController` performs an idempotency check using the `event_id` in the payload. If the event was already processed, it is ignored (responding `200 OK`).
4. The `MessageService` handles the incoming message:
   - Finds or creates a `RichWhatsAppConversation` for the sender's phone number.
   - Saves the incoming message in `rich_whatsapp_messages` with a `received` status.
   - Updates the conversation metadata: increments `unread_count` and updates `last_message_preview`.
5. The package dispatches a `WhatsAppMessageReceived` Laravel event.
6. The dashboard displays the message in real-time on next page load/selection.

---

## Resetting Unread Count

When a conversation is opened in the admin dashboard, the package automatically resets the `unread_count` for that conversation to `0`.

You can also trigger this manually in your business logic:

```php
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppConversation;

$conversation = RichWhatsAppConversation::where('phone', '201012345678')->first();
$conversation->markRead();
```
