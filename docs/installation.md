# Installation Guide

To integrate the Rich WhatsApp package into your Laravel application, follow these installation steps.

## Step 1: Install Composer Package

Install the package using Composer:

```bash
composer require richnessagency/rich-whatsapp
```

The package supports **Laravel 12.x and 13.x** out of the box and registers service providers and facades automatically through Laravel package auto-discovery.

---

## Step 2: Run the Installer

Run the package-specific installation command to publish configuration files, migrations, and assets:

```bash
php artisan rich-whatsapp:install
```

This command will:
1. Publish `config/rich-whatsapp.php` configuration.
2. Publish database migrations to `database/migrations/`.
3. Publish CSS styles to `public/vendor/rich-whatsapp/`.
4. Ask if you want to run the database migrations automatically.

---

## Step 3: Run Migrations

If you skipped migrations during the installer, run them now to set up the package-prefixed tables:

```bash
php artisan migrate
```

This creates the following tables:
- `rich_whatsapp_conversations` (chat metadata)
- `rich_whatsapp_messages` (persisted message logs)
- `rich_whatsapp_callback_events` (callback logs & webhook idempotency)

---

## Step 4: Configure Environment Variables

Add the required credentials to your `.env` file:

```env
RICH_WHATSAPP_ENABLED=true

# URL of the standalone Node.js WhatsApp Bridge
RICH_WHATSAPP_BRIDGE_URL=https://whatsapp-bridge.example.com
RICH_WHATSAPP_BRIDGE_TOKEN=your-strong-bridge-auth-token

# Token used to authenticate incoming webhooks from the bridge
RICH_WHATSAPP_CALLBACK_TOKEN=your-strong-callback-webhook-token
```

---

## Step 5: Test Connection

Verify your connection and configuration with the built-in diagnostic test command:

```bash
php artisan rich-whatsapp:test
```

If the bridge is online and credentials are valid, the command will print confirmation status.
