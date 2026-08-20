<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect WhatsApp - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
</head>
<body class="rwa-wrapper">
    <header class="rwa-header">
        <div class="rwa-logo-container">
            <div class="rwa-logo-icon">W</div>
            <div class="rwa-logo-text">Rich WhatsApp</div>
        </div>
        <div class="rwa-status-badge">
            <span class="rwa-status-dot rwa-status-qr_required"></span>
            <span id="status-label">{{ $session->status->label() }}</span>
        </div>
    </header>

    <main class="rwa-main-container" style="display: block; overflow-y: auto;">
        <div class="rwa-connect-card">
            <h2>Link Device</h2>
            <p style="color: var(--rwa-color-text-secondary); margin-bottom: 24px;">Scan the QR code below to connect your WhatsApp account.</p>

            <div class="rwa-qr-container">
                @if($qrData && $qrData->isDataUrl())
                    <img id="qr-image" class="rwa-qr-image" src="{{ $qrData->qr }}" alt="Scan Me">
                @else
                    <div id="qr-placeholder" class="rwa-qr-image" style="background: #2a3942; display: flex; align-items: center; justify-content: center; color: var(--rwa-color-text-secondary);">
                        Generating QR...
                    </div>
                @endif
            </div>

            <div class="rwa-instructions" style="margin-top: 24px;">
                <ol class="rwa-instructions-list">
                    <li>Open WhatsApp on your phone</li>
                    <li>Tap Menu or Settings and select <strong>Linked Devices</strong></li>
                    <li>Tap on <strong>Link a Device</strong></li>
                    <li>Point your phone to this screen to scan the code</li>
                </ol>
            </div>

            <div style="margin-top: 32px; display: flex; justify-content: center; gap: 16px;">
                <button onclick="window.location.reload();" class="rwa-button rwa-button-secondary">Refresh QR</button>
                <form action="{{ route('admin.whatsapp.reconnect') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="rwa-button">Force Reconnect</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const statusUrl = "{{ route('admin.whatsapp.status') }}";
        const qrUrl = "{{ route('admin.whatsapp.qr') }}";
        const dashboardUrl = "{{ route('admin.whatsapp.index') }}";
        const pollInterval = {{ $pollSeconds * 1000 }};

        async function checkStatus() {
            try {
                const res = await fetch(statusUrl);
                const data = await res.json();

                if (data.success) {
                    const status = data.status;
                    
                    document.getElementById('status-label').innerText = data.label;
                    const dot = document.querySelector('.rwa-status-dot');
                    dot.className = 'rwa-status-dot';
                    
                    if (status === 'connected') {
                        window.location.href = dashboardUrl;
                        return;
                    }
                    
                    if (status === 'qr_required') {
                        dot.classList.add('rwa-status-qr_required');
                        await updateQrCode();
                    } else if (status === 'connecting' || status === 'reconnecting') {
                        dot.classList.add('rwa-status-connecting');
                    } else {
                        dot.classList.add('rwa-status-offline');
                    }
                }
            } catch (err) {
                console.error('Error polling status:', err);
            }
        }

        async function updateQrCode() {
            try {
                const res = await fetch(qrUrl);
                const data = await res.json();

                if (data.success && data.qr) {
                    const img = document.getElementById('qr-image');
                    const placeholder = document.getElementById('qr-placeholder');
                    
                    if (img) {
                        img.src = data.qr;
                    } else if (placeholder) {
                        const newImg = document.createElement('img');
                        newImg.id = 'qr-image';
                        newImg.className = 'rwa-qr-image';
                        newImg.src = data.qr;
                        placeholder.replaceWith(newImg);
                    }
                }
            } catch (err) {
                console.error('Error updating QR:', err);
            }
        }

        // Poll status every few seconds
        setInterval(checkStatus, pollInterval);
    </script>
</body>
</html>
