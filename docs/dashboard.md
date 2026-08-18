# Admin Dashboard

The package includes a built-in admin dashboard inspired by the interaction models of premium web messaging interfaces.

## Dashboard URL
By default, the dashboard is accessible at:
```text
https://your-laravel-site.com/whatsapp
```
This route prefix is configurable using the `RICH_WHATSAPP_DASHBOARD_PREFIX` environment variable.

---

## Features

### 1. Connection Header
Shows:
- WhatsApp connection state (Connected, QR Required, Connecting, Disconnected)
- Connected phone number
- Direct buttons for **Diagnostics**, **New Chat**, and **Logout**

### 2. Conversation Sidebar
- Lists all active chats sorted by latest message activity.
- Displays unread badges matching incoming messages.
- Client-side Instant Filter: Use the search input at the top to filter chats instantly by name or phone.

### 3. Conversation View
- Visually displays message thread history (incoming left, outgoing right).
- Outputs status icons:
  - Submitted: single check (✓)
  - Sent: single check (✓)
  - Delivered: double checks (✓✓)
  - Read: blue double checks (✓✓)
  - Queued: clock icon (⏳)
  - Failed: warning alert (⚠️)

### 4. Chat Composer
Supports sending:
- Text messages
- File attachments (images and documents)

---

## Customizing Layouts

By default, the dashboard renders using its own self-contained layout. If your Laravel application already runs an admin panel, you can configure the dashboard to inherit and render inside your template.

In `config/rich-whatsapp.php`:

```php
'dashboard' => [
    'layout' => 'layouts.admin', // Points to resources/views/layouts/admin.blade.php
]
```

In your parent layout template, ensure you have:
- `@yield('content')` to output dashboard templates.
- `@stack('styles')` or similar tag to inject public styles if necessary.
