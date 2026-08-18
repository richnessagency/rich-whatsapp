# Laravel Notification Channel

Integrate WhatsApp messaging into your Laravel notification ecosystem.

## Step 1: Create a Notification

Create your notification class using the Artisan generator:

```bash
php artisan make:notification OrderShippedNotification
```

---

## Step 2: Implement Channel Logic

Edit the notification to use the `'rich-whatsapp'` channel and return a `RichWhatsAppMessage` payload:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use RichnessAgency\RichWhatsApp\Channels\RichWhatsAppMessage;

class OrderShippedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected $order
    ) {}

    public function via($notifiable): array
    {
        return ['rich-whatsapp'];
    }

    public function toRichWhatsApp($notifiable): RichWhatsAppMessage
    {
        return RichWhatsAppMessage::create()
            ->text("Your order #{$this->order->id} has been shipped!")
            ->idempotencyKey("order-shipped-{$this->order->id}");
    }
}
```

---

## Step 3: Configure Target Phone Number

The channel automatically resolves the recipient's phone number using the following precedence:

1. A phone set explicitly via message builder:
   ```php
   RichWhatsAppMessage::create()->to('201012345678')->text('...');
   ```
2. A `routeNotificationForRichWhatsapp()` method on the Notifiable model.
3. A `phone` or `phone_number` attribute on the Notifiable model.

### Example Notifiable Model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Route notifications for the WhatsApp channel.
     */
    public function routeNotificationForRichWhatsapp($notification): string
    {
        return $this->phone_number; // e.g. "201012345678"
    }
}
```
