@php
    /** @var \RichnessAgency\RichWhatsApp\DTOs\SessionInfo $session */
    $rwaActiveNav = $rwaActiveNav ?? '';
@endphp
<header class="rwa-header">
    <div class="rwa-logo-container">
        <div class="rwa-logo-icon">W</div>
        <div class="rwa-logo-text">Rich WhatsApp</div>
    </div>
    <nav style="display: flex; gap: 4px; align-items: center;">
        <a href="{{ route('rich-whatsapp.dashboard') }}" class="rwa-nav-link {{ $rwaActiveNav === 'dashboard' ? 'rwa-nav-link-active' : '' }}">Dashboard</a>
        <a href="{{ route('rich-whatsapp.chats') }}" class="rwa-nav-link {{ $rwaActiveNav === 'chats' ? 'rwa-nav-link-active' : '' }}">Chats</a>
        <a href="{{ route('rich-whatsapp.contacts') }}" class="rwa-nav-link {{ $rwaActiveNav === 'contacts' ? 'rwa-nav-link-active' : '' }}">Contacts</a>
    </nav>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="{{ route('rich-whatsapp.settings') }}" class="rwa-button rwa-button-secondary" style="padding: 6px 14px;">Diagnostics</a>

        @if($session->status->isConnected())
            <button onclick="document.getElementById('rwa-logout-dialog').showModal();" class="rwa-button rwa-button-danger" style="padding: 6px 14px;">Logout</button>
        @else
            <a href="{{ route('rich-whatsapp.connect') }}" class="rwa-button" style="padding: 6px 14px;">Connect</a>
        @endif

        <div class="rwa-status-badge">
            @if($session->status->isConnected())
                <span class="rwa-status-dot rwa-status-connected"></span>
            @else
                <span class="rwa-status-dot rwa-status-offline"></span>
            @endif
            <span>{{ $session->status->label() }}</span>
        </div>
    </div>
</header>

<dialog id="rwa-logout-dialog" class="rwa-dialog">
    <h3 style="margin-top: 0;">Unlink WhatsApp Session?</h3>
    <p style="font-size: 14px; line-height: 1.5; color: var(--rwa-color-text-secondary);">Logging out will unlink this WhatsApp session and a new QR scan will be required. Are you sure you want to continue?</p>
    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="document.getElementById('rwa-logout-dialog').close();" class="rwa-button rwa-button-secondary">Cancel</button>
        <form action="{{ route('rich-whatsapp.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="rwa-button rwa-button-danger">Logout</button>
        </form>
    </div>
</dialog>