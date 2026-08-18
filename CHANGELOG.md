# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial package scaffold.
- `WhatsAppBridgeClient` with health, session, messages and contacts endpoints.
- `WhatsAppService` (contract `WhatsApp`) with fluent `to()->send()` API, idempotency keys and result DTOs.
- `PhoneNumberService` normalization using `RICH_WHATSAPP_DEFAULT_COUNTRY_CODE`.
- Session layer: status, QR, start, reconnect, logout, health.
- Persistence: `rich_whatsapp_conversations`, `rich_whatsapp_messages`, `rich_whatsapp_callback_events` tables.
- Callback receiver with constant-time token verification, idempotency and Laravel events.
- Laravel notification channel (`rich-whatsapp`) with `RichWhatsAppMessage`.
- WhatsApp-style admin dashboard with QR connect flow, conversations, messages and composer.
- Artisan commands: `rich-whatsapp:install`, `rich-whatsapp:test`, `rich-whatsapp:health`, `rich-whatsapp:status`, `rich-whatsapp:reconnect`, `rich-whatsapp:logout`.
- Documentation in `docs/`.

## [0.1.0] - 2026-08-18

- Development release. Public API still stabilizing; do not rely on this tag in production yet.