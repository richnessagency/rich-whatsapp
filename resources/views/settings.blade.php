<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostics & Settings - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
    <style>
        /* Fallback styling support */
        {!! file_get_contents(__DIR__ . '/../../css/app.css') !!}
    </style>
</head>
<body class="rwa-wrapper">
    <header class="rwa-header">
        <div class="rwa-logo-container">
            <div class="rwa-logo-icon">W</div>
            <div class="rwa-logo-text">Rich WhatsApp</div>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ route('rich-whatsapp.dashboard') }}" class="rwa-button rwa-button-secondary">Back to Chats</a>
        </div>
    </header>

    <main class="rwa-main-container" style="display: block; overflow-y: auto; padding: 24px;">
        <div style="max-width: 600px; margin: 0 auto; background: var(--rwa-bg-panel); border: 1px solid var(--rwa-border-color); border-radius: var(--rwa-radius); padding: 32px; box-shadow: var(--rwa-shadow);">
            <h2 style="margin-top: 0; margin-bottom: 24px;">Package Settings & Diagnostics</h2>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Package Status</span>
                    <strong>{{ $enabled ? 'Enabled' : 'Disabled' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Bridge URL Configured</span>
                    <strong>{{ $configured ? 'Yes' : 'No' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Node Bridge URL</span>
                    <strong style="word-break: break-all;">{{ $bridgeUrl ?: 'Not Configured' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Country Code Default</span>
                    <strong>+{{ $defaultCountryCode ?: '20' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Local Message Storage</span>
                    <strong>{{ $storeMessages ? 'Enabled' : 'Disabled' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Bridge Token Configured</span>
                    <strong>{{ config('rich-whatsapp.bridge_token') ? 'Yes (Hidden)' : 'No' }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rwa-border-color); padding-bottom: 8px;">
                    <span style="color: var(--rwa-color-text-secondary);">Callback Token Configured</span>
                    <strong>{{ config('rich-whatsapp.callback_token') ? 'Yes (Hidden)' : 'No' }}</strong>
                </div>
            </div>

            <div style="margin-top: 32px;">
                <p style="font-size: 13px; color: var(--rwa-color-text-secondary); line-height: 1.5;">
                    * Secrets and authorization tokens are redacted and never printed directly on any interface or logs for security guarantees. To change configurations, edit your application's <code>.env</code> file.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
