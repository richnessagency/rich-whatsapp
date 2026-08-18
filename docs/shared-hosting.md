# Shared Hosting Deployment Guide

This package is optimized to be deployed in shared hosting environments.

## Shared Hosting Advantages

Unlike typical Laravel WhatsApp integrations, this package has **zero hosting footprint**:

- ❌ No Puppeteer or Chrome installations required inside your web folder.
- ❌ No persistent Node.js daemon running inside your PHP process memory.
- ❌ No database requirements (like Redis, horizon) for queuing.
- ❌ No WebSocket port opening required on your hosting firewall.

---

## Deployment Steps

1. **Deploy your Laravel application** to your shared hosting account normally (via FTP, Git, or Panel).
2. **Install composer package** on your local machine or during deployment:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. **Configure Environment Variables** in your hosting control panel's `.env` configuration (e.g. cPanel, Plesk):
   ```env
   RICH_WHATSAPP_ENABLED=true
   RICH_WHATSAPP_BRIDGE_URL=https://your-node-bridge.example.com
   RICH_WHATSAPP_BRIDGE_TOKEN=your-token
   RICH_WHATSAPP_CALLBACK_TOKEN=your-callback-token
   ```
4. **Publish package assets** to your public folder:
   ```bash
   php artisan vendor:publish --tag=rich-whatsapp-assets --force
   ```
5. **Run database migrations** via your hosting panel or terminal:
   ```bash
   php artisan migrate
   ```
6. **Set up Node Bridge Callbacks**
   Configure your standalone Node Bridge (deployed on a VPS or separate persistent Node app) to forward webhooks to:
   ```text
   https://your-shared-hosting-site.com/rich-whatsapp/api/callback
   ```
7. Open the dashboard at `https://your-shared-hosting-site.com/whatsapp` and connect your device.
