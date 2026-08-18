# Configuration Reference

The package settings live in `config/rich-whatsapp.php`. You can configure all values using environment variables.

## Connection Parameters

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `enabled` | `RICH_WHATSAPP_ENABLED` | `true` | Set to false to disable all bridge communication. |
| `bridge_url` | `RICH_WHATSAPP_BRIDGE_URL` | `''` | Base URL of the Node.js WhatsApp Bridge. |
| `bridge_token` | `RICH_WHATSAPP_BRIDGE_TOKEN` | `''` | Secret Bearer Token for sending requests to the bridge. |
| `callback_token` | `RICH_WHATSAPP_CALLBACK_TOKEN` | `''` | Secret token used to authorize incoming callbacks from the bridge. |

## Timings & Limits

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `http_timeout` | `RICH_WHATSAPP_HTTP_TIMEOUT` | `10` | Request timeout in seconds. |
| `connect_timeout` | `RICH_WHATSAPP_CONNECT_TIMEOUT` | `3` | Connection handshake timeout in seconds. |
| `media_max_mb` | `RICH_WHATSAPP_MEDIA_MAX_MB` | `10` | Maximum file upload size allowed for outbound media messages. |

## Message Persistence

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `store_messages` | `RICH_WHATSAPP_STORE_MESSAGES` | `true` | Save outgoing and incoming messages locally. |
| `store_incoming` | `RICH_WHATSAPP_STORE_INCOMING` | `true` | Persist incoming message history. |
| `store_outgoing` | `RICH_WHATSAPP_STORE_OUTGOING` | `true` | Persist outgoing message history. |

## Admin Dashboard

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `dashboard_enabled` | `RICH_WHATSAPP_DASHBOARD_ENABLED` | `true` | Registers dashboard web routes. |
| `dashboard_prefix` | `RICH_WHATSAPP_DASHBOARD_PREFIX` | `'whatsapp'` | Route path prefix (e.g. `your-app.com/whatsapp`). |
| `dashboard_middleware` | _None_ | `['web', 'auth']` | Middleware array to authenticate dashboard routes. |
| `dashboard.layout` | _None_ | `null` | Optional parent template that the dashboard should extend (e.g. `'layouts.app'`). |

## QR Scan Polling

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `qr_poll_seconds` | `RICH_WHATSAPP_QR_POLL_SECONDS` | `3` | Polling interval for QR code scan status in the Connect view. |

## Localization

| Key | Env Var | Default | Description |
|-----|---------|---------|-------------|
| `default_country_code` | `RICH_WHATSAPP_DEFAULT_COUNTRY_CODE` | `'20'` | Fallback country code to normalize national number formats (e.g., `'20'` for Egypt). |
| `log_message_content` | `RICH_WHATSAPP_LOG_MESSAGE_CONTENT` | `false` | Log full text content of messages during errors. Keep `false` in production. |
