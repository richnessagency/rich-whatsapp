# Programmatic Sending API

The package provides clean APIs to send text and media messages from anywhere within your Laravel application.

---

## 1. Simple Send

Use the `RichWhatsApp` Facade to send a text message in a single method call:

```php
use RichnessAgency\RichWhatsApp\Facades\RichWhatsApp;

$result = RichWhatsApp::sendText('201012345678', 'Hello! Your order has been shipped.');

if ($result->successful()) {
    $messageId = $result->messageId();
    $status = $result->status();
}
```

---

## 2. Fluent Message Builder

Use the fluent builder interface for more advanced settings:

```php
use RichnessAgency\RichWhatsApp\Facades\RichWhatsApp;

$result = RichWhatsApp::to('201012345678')
    ->message('Hello! Please review your order.')
    ->idempotencyKey('order-update-44')
    ->send();
```

---

## 3. Idempotency Key Handling

To prevent duplicate notifications during network retries or queue jobs, you can pass a unique `idempotencyKey` (mapped to `request_id` on the bridge):

```php
// If this code runs twice, the second execution returns the cached result without sending a duplicate message.
RichWhatsApp::to($customer->phone)
    ->idempotencyKey("payment-received-{$invoice->id}")
    ->send("We received your payment for Invoice #{$invoice->id}. Thank you!");
```

---

## 4. Phone Number Normalization

The package automatically parses and normalizes phone numbers. By default, it uses the country code configured in your `.env`:

```env
RICH_WHATSAPP_DEFAULT_COUNTRY_CODE=20
```

| Input | Normalization Result |
|-------|----------------------|
| `+20 10 1234 5678` | `201012345678` |
| `01012345678` | `201012345678` (leading zero stripped, country code prepended) |
| `1012345678` | `201012345678` (country code prepended) |
| `201012345678` | `201012345678` (assumed international already) |

---

## 5. Dependency Injection

If you prefer dependency injection, bind the `RichnessAgency\RichWhatsApp\Contracts\WhatsApp` interface:

```php
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class OrderController extends Controller
{
    public function __construct(
        protected WhatsApp $whatsapp
    ) {}

    public function ship(string $orderId)
    {
        $this->whatsapp->to('201012345678')
            ->idempotencyKey("ship-order-{$orderId}")
            ->send("Order #{$orderId} has been shipped.");
    }
}
```
