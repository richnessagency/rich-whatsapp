@php
    /** @var \RichnessAgency\RichWhatsApp\DTOs\PagedList $contacts */
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
</head>
<body class="rwa-wrapper">
    @include('rich-whatsapp::partials.header', ['rwaActiveNav' => 'contacts'])

    @if(! $session->status->isConnected())
        <div style="background-color: var(--rwa-danger); color: #fff; padding: 10px 24px; font-weight: 500; font-size: 14px;">
            <span>WhatsApp is disconnected — contacts are only available from the bridge while connected.</span>
        </div>
    @endif

    <main class="rwa-main-container">
        <aside class="rwa-sidebar rwa-contacts-list">
            <div class="rwa-search-box">
                <form action="{{ route('rich-whatsapp.contacts') }}" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="q" value="{{ $currentQuery }}" class="rwa-search-input" placeholder="Search contacts...">
                    <button type="submit" class="rwa-button rwa-button-secondary" style="padding: 6px 12px;">Search</button>
                </form>
            </div>

            <div style="flex: 1; overflow-y: auto;">
                @forelse($contacts->items as $contact)
                    <a href="{{ route('rich-whatsapp.chat', ['jid' => $contact->jid]) }}" class="rwa-conv-item" style="display: flex; align-items: center;">
                        <div class="rwa-conv-avatar">{{ mb_substr($contact->name, 0, 2) }}</div>
                        <div class="rwa-conv-details" style="min-width: 0;">
                            <div class="rwa-conv-header">
                                <span class="rwa-conv-name">{{ $contact->name }}</span>
                            </div>
                            <div class="rwa-conv-header" style="margin-top: 2px;">
                                <span class="rwa-conv-preview">+{{ $contact->phone ?: '' }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--rwa-color-text-secondary); font-size: 14px;">
                        @if($currentQuery !== '')
                            No contacts match "{{ $currentQuery }}".
                        @else
                            No contacts synced yet. Connect the WhatsApp session to sync your contacts.
                        @endif
                    </div>
                @endforelse
            </div>

            @if(count($contacts->items) > 0)
                <div style="padding: 10px 16px; font-size: 12px; color: var(--rwa-color-text-secondary); border-top: 1px solid var(--rwa-border-color);">
                    {{ $contacts->total }} contacts
                </div>
            @endif
        </aside>

        <section class="rwa-chat-pane">
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--rwa-color-text-secondary);">
                <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.4;">👤</div>
                <h3 style="font-weight: 400;">Select a contact</h3>
                <p style="font-size: 14px;">Open a conversation with any synced contact.</p>
            </div>
        </section>
    </main>
</body>
</html>