# Rich WhatsApp Laravel Package

A production-grade, highly-extensible Laravel package that integrates Laravel applications with the standalone Node.js WhatsApp Bridge to provide a full WhatsApp-like dashboard and robust developer APIs.

---

## Architecture

```text
Laravel Application
        │
        │  Rich WhatsApp Package (Facade, Service, Channel)
        │  HTTPS / REST API with Bearer token
        ▼
Node.js WhatsApp Bridge (standalone service)
        │
        │  WhatsApp Web protocol (Baileys)
        ▼
WhatsApp Web Session
```

The package implements zero WhatsApp protocol details itself, keeping your Laravel application extremely lightweight, secure, and isolated from Node.js process lifecycles.

---

## Features

- **WhatsApp Web-style Admin Dashboard** — responsive Blade-based chat interface.
- **QR Connection Flow** — automatic polling and linking in the UI.
- **Fluent Developer API** — clean sending via Facades, DI, or fluent builder.
- **Laravel Notification Channel** — seamless integration with custom notification messages.
- **Local Persistence** — optional database history of conversations & messages.
- **Idempotency** — unique request tracking to prevent duplicate message sending.
- **Incoming Message Events** — webhook callback handling with secure token verification.
- **Diagnostics & Status Monitoring** — health check commands and status displays.

---

## Installation

```bash
# 1. Install via Composer
composer require richnessagency/rich-whatsapp

# 2. Run the package installer
php artisan rich-whatsapp:install

# 3. Run migrations to create prefixed tables
php artisan migrate
```

For detailed options, refer to the [Installation Documentation](docs/installation.md).

---

## Configuration

Configure the package using your `.env` file:

```env
RICH_WHATSAPP_ENABLED=true

# Standalone Node.js Bridge credentials
RICH_WHATSAPP_BRIDGE_URL=https://whatsapp-node.example.com
RICH_WHATSAPP_BRIDGE_TOKEN=your-bridge-secret-token

# Separate token to authorize incoming webhooks
RICH_WHATSAPP_CALLBACK_TOKEN=your-callback-secret-token

# Dashboard settings
RICH_WHATSAPP_DASHBOARD_ENABLED=true
RICH_WHATSAPP_DASHBOARD_PREFIX=whatsapp
```

See [Configuration Documentation](docs/configuration.md) for a full description of all 15+ settings.

---

## Connecting WhatsApp

1. Point `RICH_WHATSAPP_BRIDGE_URL` and `RICH_WHATSAPP_BRIDGE_TOKEN` to your running Node.js Bridge.
2. Setup the callback URL on your Node Bridge config to point to:
   ```text
   https://your-laravel-site.com/rich-whatsapp/api/callback
   ```
3. Open your browser to the configured prefix (default: `https://your-laravel-site.com/whatsapp`).
4. Click **Link Device**, scan the QR code using your WhatsApp mobile app under "Linked Devices", and wait until status becomes **Connected**.

---

## Programmatic Usage

### Simple Send

```php
use RichnessAgency\RichWhatsApp\Facades\RichWhatsApp;

RichWhatsApp::sendText('201012345678', 'Hello! Your order has been placed.');
```

### Fluent Send with Idempotency

```php
RichWhatsApp::to($customerPhone)
    ->idempotencyKey("order-{$order->id}-preparing")
    ->send('Your order is now being prepared.');
```

### Sending Media

```php
RichWhatsApp::to($customerPhone)
    ->sendImage(storage_path('app/receipt.png'), 'Your receipt');

RichWhatsApp::to($customerPhone)
    ->sendDocument(storage_path('app/invoice.pdf'), 'invoice_123.pdf');
```

For more examples, see the [Sending Messages Documentation](docs/sending-messages.md).

---

## Laravel Notifications

Use `rich-whatsapp` directly inside Laravel's notification channel ecosystem:

```php
use Illuminate\Notifications\Notification;
use RichnessAgency\RichWhatsApp\Channels\RichWhatsAppMessage;

class OrderReadyNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['rich-whatsapp'];
    }

    public function toRichWhatsApp($notifiable): RichWhatsAppMessage
    {
        return RichWhatsAppMessage::create('Your order is ready!')
            ->idempotencyKey("order-ready-{$this->orderId}");
    }
}
```

---

## Incoming Webhooks & Events

The package exposes public Laravel events that your application can listen to:

- `WhatsAppMessageReceived`
- `WhatsAppMessageSent`
- `WhatsAppMessageDelivered`
- `WhatsAppMessageRead`
- `WhatsAppMessageFailed`
- `WhatsAppSessionConnected`
- `WhatsAppSessionDisconnected`
- `WhatsAppSessionQrRequired`
- `WhatsAppSessionStatusChanged`

Configure a standard listener to react to incoming messages:

```php
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageReceived;

Event::listen(WhatsAppMessageReceived::class, function ($event) {
    Log::info('New message from ' . $event->message->from_phone . ': ' . $event->message->body);
});
```

See [Callbacks Documentation](docs/callbacks.md) for payload models.

---

## Shared Hosting Deployment

Since the Laravel package doesn't launch Puppeteer, run a Node.js runtime locally inside Laravel, or open persistent WebSocket loops, it is **100% compatible with shared hosting** environments. The only requirement is outgoing HTTPS client access to communicate with the standalone Node.js Bridge.

For deployment details, see the [Shared Hosting Guide](docs/shared-hosting.md).

---

## Troubleshooting & Support

Refer to the [Troubleshooting Documentation](docs/troubleshooting.md) for details on:
- Bridge offline errors
- Configuration validation exceptions
- Status/QR polling debugs
- Phone number normalization failures
