# Media Messages

The package supports sending images and documents directly from the local filesystem or via multipart dashboard uploads.

---

## Supported Media Types
The package automatically infers the media type based on the file MIME type:

| MIME Prefix | Inferred Type |
|-------------|---------------|
| `image/*` | `image` |
| `video/*` | `video` |
| `audio/*` | `audio` |
| Other | `document` |

---

## Programmatic Media Sending

### 1. Fluent Builder Send

```php
use RichnessAgency\RichWhatsApp\Facades\RichWhatsApp;

// Send Image with Caption
RichWhatsApp::to('201012345678')
    ->idempotencyKey('receipt-123')
    ->sendImage(storage_path('app/receipt.png'), 'Here is your receipt');

// Send Document
RichWhatsApp::to('201012345678')
    ->sendDocument(storage_path('app/invoice.pdf'), 'invoice_123.pdf');
```

### 2. General File Sending

```php
use RichnessAgency\RichWhatsApp\Enums\MediaType;

RichWhatsApp::to('201012345678')
    ->sendFile(
        path: storage_path('app/video.mp4'),
        type: MediaType::Video,
        filename: 'promotional_video.mp4'
    );
```

---

## Safety & Validations

1. **File Existence**
   The package validates that the file exists locally before starting the request. If missing, it immediately returns a failed `MessageResult` without attempting connection to the bridge.

2. **File Size Enforcement**
   The package validates that the file size does not exceed the configuration limits:
   ```env
   RICH_WHATSAPP_MEDIA_MAX_MB=10
   ```
   If a file exceeds this limit, it is rejected immediately with a size validation exception.

3. **Stream Upload**
   Files are attached using standard Laravel Http multipart file streams, ensuring that large files are never buffered entirely into Laravel memory.
