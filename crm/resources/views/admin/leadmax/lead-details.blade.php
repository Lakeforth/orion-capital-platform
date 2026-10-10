@php
    $leadTz = $lead->timezone ?: 'UTC';
    try { new \DateTimeZone($leadTz); } catch (\Exception $e) { $leadTz = 'UTC'; }
    $lastSeenDt = $lead->last_seen ? $lead->last_seen->copy()->setTimezone($leadTz) : null;
    $lastSeenStr = $lastSeenDt ? $lastSeenDt->format('l, jS F, Y, H:i') . ' ' . $lastSeenDt->format('A') : null;
    $lastSeenDevice = match(strtolower($lead->device_type ?? '')) { 'mobile','phone' => 'Mobile', 'tablet' => 'Tablet', 'desktop','web' => 'Desktop', default => null };
    $email = str_ends_with($lead->email ?? '', '@leadmax-contact.invalid') ? 'Not provided' : ($lead->email ?: 'Not provided');
    $detailUrl = fn (array $query = []) => route('admin.leadmax.leads.show', $lead->id).($query ? '?'.http_build_query($query) : '');
    // The KYC view, its filters, sort and page survive opening and closing one of its dialogs (NC-12, NC-23.3).
    $kycPage = $tab === 'kyc' ? array_filter(request()->only(['kyc_view', 'kh_search', 'kh_from', 'kh_to', 'kh_status', 'kh_type', 'kh_sort', 'kh_dir', 'kh_page', 'kh_per', 'ka_page'])) : [];
    $tabUrl = fn (string $name) => $detailUrl(['tab' => $name, ...($name === 'kyc' ? $kycPage : [])]);
    $dialogUrl = fn (string $name) => $detailUrl(['tab' => $tab, ...$kycPage, 'dialog' => $name]);
    $phoneDigits = $lead->phone ? preg_replace('/[^+0-9]/', '', $lead->phone) : null;
    $phoneHref = $phoneDigits ? 'tel:'.$phoneDigits : null;
    $smsHref = $phoneDigits && ! $communicationsBlocked ? 'sms:'.$phoneDigits : null;
    $whatsappHref = $phoneDigits && ! $communicationsBlocked ? 'https://wa.me/'.ltrim($phoneDigits, '+') : null;
    $hasEmail = $lead->email && ! str_ends_with($lead->email, '@leadmax-contact.invalid');
    $openTickets = $tickets->whereIn('status', ['open', 'in_progress']);
    $blockUrl = route('admin.leadmax.leads.block', $lead->id);
    // [label, kind, href|url, extra, confirm]. Kinds: link, post, send-credentials, copy-link, disabled, soon (no backend yet).
    $menus = [
        ['actions', '⚡', 'Actions', [
            // One login, straight into the lead's trade room (an account is created if the lead has none).
            ['Login as Client', 'link', route('admin.leadmax.leads.webtrader-login', $lead->id), 'blank'],
            ['Change Lead Status', 'link', $dialogUrl('lead-status')],
            ['Change Password', 'link', $dialogUrl('change-password')],
            ['Assign to Agent', is_admin() ? 'link' : 'disabled', $dialogUrl('assign')],
            ['Send Credentials', $hasEmail ? 'send-credentials' : 'disabled', route('admin.leadmax.leads.send-credentials', $lead->id)],
            ['Copy Link', 'copy-link', route('admin.leadmax.leads.copy-link', $lead->id)],
        ]],
        ['communications', '▣', 'Communications', [
            ['Email', $hasEmail && ! $communicationsBlocked ? 'link' : 'disabled', $dialogUrl('email')],
            ['SMS / Text', $smsHref ? 'link' : 'disabled', $smsHref],
            ['Phone Call', $phoneHref && ! $communicationsBlocked ? 'link' : 'disabled', $phoneHref],
            ['WhatsApp', $whatsappHref ? 'link' : 'disabled', $whatsappHref, 'blank'],
            ['Live Chat', 'link', route('admin.leadmax.chat.clients', ['with' => $lead->id])],
            ['Video Call', 'soon'],
        ]],
        ['block', '◉', 'Block/Unblock', [
            ['Block User', $lead->banned ? 'disabled' : 'post', $blockUrl, 'block', 'Block this user? They will be signed out and unable to log in.'],
            ['Unblock User', $lead->banned ? 'post' : 'disabled', $blockUrl, 'unblock'],
            $communicationsBlocked
                ? ['Unblock All Communications', 'post', $blockUrl, 'unblock_communications']
                : ['Block All Communications', 'post', $blockUrl, 'block_communications', 'Block all emails and messages to this lead?'],
        ]],
        ['finances', '▣', 'Finances', [
            ['Create Invoice', is_admin() ? 'link' : 'disabled', $dialogUrl('invoice')],
            ['Create Credit Note', is_admin() ? 'link' : 'disabled', $dialogUrl('credit-note')],
            // Add Payment, Adjust Balance and Issue Withdrawal are buttons on the Finances tab itself.
            ['View Transactions', 'link', $tabUrl('finances')],
            ['Financial Summary', 'link', $tabUrl('finances').'#lm-financial-summary'],
        ]],
        ['service', '♙', 'Service', [
            ['Create Service Request', 'link', $dialogUrl('service')],
            ['View Service History', 'link', $dialogUrl('service-history')],
            ['Escalate Issue', $openTickets->isNotEmpty() ? 'link' : 'disabled', $dialogUrl('escalate')],
            ['Assign to Support Agent', $openTickets->isNotEmpty() ? 'link' : 'disabled', $dialogUrl('assign-support')],
            ['Change Priority', $openTickets->isNotEmpty() ? 'link' : 'disabled', $dialogUrl('ticket-priority')],
            ['Cancel Service', $openTickets->isNotEmpty() ? 'link' : 'disabled', $dialogUrl('cancel-service')],
        ]],
        ['call-history', '◷', 'Create call history', []],
        ['reminder', '◷', 'Create event', []],
        ['popup', '▣', 'Send popup', [
            ['Send Email', $hasEmail && ! $communicationsBlocked ? 'link' : 'disabled', $dialogUrl('email')],
            ['Send SMS', $smsHref ? 'link' : 'disabled', $smsHref],
            ['Send WhatsApp', $whatsappHref ? 'link' : 'disabled', $whatsappHref, 'blank'],
            ['Send Push Notification', 'soon'],
            ['Send In-App Message', $communicationsBlocked ? 'disabled' : 'link', $dialogUrl('message')],
            ['Schedule Campaign', 'soon'],
        ]],
    ];
    // The action bar's icons as line drawings (user request, 10 Oct 2026); they replace the text
    // glyphs above, which rendered as unclear squares and clocks.
    $actionIcons = [
        'actions' => '<path d="M13 2.5 4.5 13.5H11L10 21.5l8.5-11H13l0-8Z"/>',
        'communications' => '<path d="M4 4.5h11v8H8.5L4 16V4.5Z"/><path d="M18 8.5h2v11l-3.5-3H11v-2"/>',
        'block' => '<circle cx="12" cy="12" r="8.5"/><path d="m6 6 12 12"/>',
        'finances' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 9.5v5M17.5 9.5v5"/>',
        'service' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.4-1.6 2.5-4.5 2.5H13"/>',
        'call-history' => '<path d="M20.5 16.4v2.7a1.9 1.9 0 0 1-2.1 1.9A17.6 17.6 0 0 1 3 5.6 1.9 1.9 0 0 1 4.9 3.5h2.7a1.9 1.9 0 0 1 1.9 1.6c.1.9.4 1.8.7 2.6a1.9 1.9 0 0 1-.4 2L8.6 10.9a15 15 0 0 0 4.5 4.5l1.2-1.2a1.9 1.9 0 0 1 2-.4c.8.3 1.7.6 2.6.7a1.9 1.9 0 0 1 1.6 1.9Z"/><path d="M15 3.5h5.5V9M20.5 3.5 15 9"/>',
        'reminder' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17M12 13v5M9.5 15.5h5"/>',
        'popup' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M6.5 6.5h.01M9 6.5h.01"/>',
    ];
    $tabs = ['comments' => 'Comment ('.$notes->count().')', 'finances' => 'Finances ('.($transactionCount + $invoices->count()).')', 'trading' => 'Trading Accounts', 'kyc' => 'KYC', 'history' => 'Activity Log', 'calls' => 'Calls', 'chat' => 'Chat', 'signals' => 'Signals', 'sessions' => 'Active Sessions'];
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage" :page-title="trim($lead->first_name.' '.$lead->last_name) ?: 'Lead Details'">
    <div class="lm-detail-page" data-lm-lead-detail>
        @php $fromFinances = request('from') === 'finances'; @endphp<header class="lm-detail-header"><div><h1>{{ $fromFinances ? 'User Details' : 'Lead Details' }}</h1>@if($fromFinances)<a class="lm-back-link" href="{{ route('admin.leadmax.finances') }}">← Back to Finances</a>@else<a class="lm-back-link" href="{{ route('admin.leadmax.leads') }}">← Back to Leads</a>@endif</div><div class="lm-detail-header-right"><div class="lm-detail-breadcrumbs">@if($fromFinances)<a href="{{ route('admin.leadmax.finances') }}">Finances</a>@else<a href="{{ route('admin.leadmax.leads') }}">Leads</a>@endif<i>/</i><span>Profile</span><i>/</i><b>{{ $lead->name ?: ($lead->account_id ?: substr($lead->id, 0, 8)) }}</b></div><div class="lm-detail-navigation">@if($previousLeadId)<a href="{{ route('admin.leadmax.leads.show', $previousLeadId) }}">‹ Previous</a>@else<span class="lm-nav-disabled" aria-disabled="true">‹ Previous</span>@endif @if($nextLeadId)<a href="{{ route('admin.leadmax.leads.show', $nextLeadId) }}">Next ›</a>@else<span class="lm-nav-disabled" aria-disabled="true">Next ›</span>@endif<div class="lm-action-dropdown lm-more-dropdown" data-lm-action-dropdown><button type="button" class="lm-more-trigger" aria-label="More actions" aria-haspopup="true" aria-expanded="false" data-lm-action-trigger="more">⋯</button><div class="lm-action-menu lm-more-menu" data-lm-action-menu="more" hidden role="menu"><button type="button" role="menuitem" data-lm-action-item data-lm-field-focus>✎ Edit details</button><button type="button" role="menuitem" data-lm-action-item data-lm-href="{{ route('admin.leadmax.leads.webtrader-login', $lead->id) }}" data-lm-target="_blank">↗ Login as Client</button><button type="button" role="menuitem" data-lm-action-item data-lm-copy-text="{{ $lead->account_id ?: '--' }}">▣ Copy Lead ID</button><button type="button" role="menuitem" data-lm-action-item data-lm-copy-text="{{ route('admin.leadmax.leads.show', $lead->id) }}">🔗 Copy page link</button><button type="button" role="menuitem" data-lm-action-item data-lm-print>🖨 Print</button>@if(is_admin())<button type="button" role="menuitem" class="is-danger" data-lm-action-item data-lm-href="{{ $dialogUrl('delete') }}">🗑 Delete lead</button>@endif</div></div></div></div></header>
        @if(session('success'))<p class="lm-detail-flash" role="status">{{ session('success') }}</p>@endif
        @if($lead->banned || $communicationsBlocked)<p class="lm-detail-flash lm-detail-flash-warning" role="status">{{ $lead->banned ? 'This user is blocked and cannot log in.' : '' }} {{ $communicationsBlocked ? 'All communications to this lead are blocked.' : '' }}</p>@endif
        <div class="lm-detail-actions"><div class="lm-action-buttons">
            @foreach($menus as [$key, $icon, $label, $items])
                <div class="lm-action-dropdown" data-lm-action-dropdown>
                    <button type="button" class="lm-action-trigger" aria-expanded="false" aria-haspopup="true" data-lm-action-trigger="{{ $key }}"><span aria-hidden="true" class="lm-action-glyph lm-action-glyph-{{ $key }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $actionIcons[$key] ?? '' !!}</svg></span><span>{{ $label }}</span><span class="lm-action-chevron" aria-hidden="true"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span></button>
                    <div class="lm-action-menu" data-lm-action-menu="{{ $key }}" hidden role="menu">
                        @if($key === "call-history")<form class="lm-call-history-form" data-lm-submit action="{{ route("admin.leadmax.leads.call-history", $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('calls') }}">@csrf<label>Call Type<select name="call_type" required><option value="outbound" selected>Outbound</option><option value="inbound">Inbound</option><option value="missed">Missed</option></select></label><label>Call Status<select name="call_status" required><option value="answered" selected>Answered</option><option value="unanswered">Unanswered</option><option value="voicemail">Voicemail</option></select></label><label>Duration<input name="duration" type="text" value="00:00:00" placeholder="HH:MM:SS" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" required></label><label>Date &amp; Time<input name="called_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" required></label><label>Notes / Comment<textarea name="notes" placeholder="Describe the client's response, interest level,
or next follow-up dates..."></textarea></label><p role="alert" class="lm-form-error" hidden></p><footer><button type="button" class="lm-button" data-lm-action-item>Cancel</button><button class="lm-button primary">Save Call</button></footer></form>@elseif($key === "reminder")<form class="lm-reminder-form" data-lm-submit action="{{ route("admin.leadmax.leads.reminder", $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('calls') }}">@csrf<div class="lm-reminder-row"><label>Event Type<select name="event_type" required>@foreach(\App\Http\Controllers\Admin\LeadMaxToolsController::EVENT_TYPES as $value => $label)<option value="{{ $value }}">{{ \Illuminate\Support\Str::singular($label) }}</option>@endforeach</select></label><label>Subject<input name="subject" required maxlength="255" placeholder="e.g. Call back regarding the proposal"></label></div><div class="lm-reminder-row"><label>Date<input name="reminder_date" type="date" value="{{ now()->addDay()->toDateString() }}" required></label><label>Time<input name="reminder_time" type="time" value="{{ now()->format('H:i') }}" required></label></div><div class="lm-reminder-row"><label>Priority<select name="priority" required><option value="high">🔴 High Priority</option><option value="medium" selected>🟡 Medium Priority</option><option value="low">🔵 Low Priority</option></select></label><label>Agent<select name="agent_id"><option value="">{{ $lead->manager?->name ? $lead->manager->name.' (lead owner)' : 'Unassigned' }}</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected($lead->manager_id === $member->id)>{{ $member->name }}</option>@endforeach</select></label></div><label>Notes<textarea name="notes" placeholder="What should happen when this reminder is due?"></textarea></label><p role="alert" class="lm-form-error" hidden></p><footer><button type="button" class="lm-button" data-lm-action-item>Cancel</button><button class="lm-button primary">Save Event</button></footer></form>@else @foreach($items as $entry)
@php [$itemLabel, $kind] = $entry; $href = $entry[2] ?? null; @endphp
@if($kind === 'link')
<button type="button" role="menuitem" data-lm-action-item data-lm-href="{{ $href }}" @if(($entry[3] ?? null) === 'blank') data-lm-target="_blank" @endif><span class="lm-action-item-icon lm-action-icon-{{ $key }}-{{ $loop->iteration }}" aria-hidden="true"></span><span>{{ $itemLabel }}</span></button>
@elseif($kind === 'post')
<button type="button" role="menuitem" data-lm-action-item data-lm-post="{{ $href }}" data-lm-post-action="{{ $entry[3] }}" @if($entry[4] ?? null) data-lm-confirm="{{ $entry[4] }}" @endif><span class="lm-action-item-icon lm-action-icon-{{ $key }}-{{ $loop->iteration }}" aria-hidden="true"></span><span>{{ $itemLabel }}</span></button>
@elseif($kind === 'send-credentials')
<button type="button" role="menuitem" data-lm-action-item data-lm-send-credentials="{{ $href }}" data-lm-confirm="Send login credentials to {{ $lead->email }}? This resets the lead's password."><span class="lm-action-item-icon lm-action-icon-{{ $key }}-{{ $loop->iteration }}" aria-hidden="true"></span><span>{{ $itemLabel }}</span></button>
@elseif($kind === 'copy-link')
<button type="button" role="menuitem" data-lm-action-item data-lm-copy-link="{{ $href }}"><span class="lm-action-item-icon lm-action-icon-{{ $key }}-{{ $loop->iteration }}" aria-hidden="true"></span><span>{{ $itemLabel }}</span></button>
@else
<button type="button" role="menuitem" data-lm-action-item disabled aria-disabled="true" @if($kind === 'soon') title="Coming soon" @endif><span class="lm-action-item-icon lm-action-icon-{{ $key }}-{{ $loop->iteration }}" aria-hidden="true"></span><span>{{ $itemLabel }}</span>@if($kind === 'soon')<small class="lm-action-soon">Soon</small>@endif</button>
@endif
@endforeach
@endif
                    </div>
                </div>
            @endforeach
        </div><button type="button" class="lm-detail-collapse" aria-label="Collapse details" aria-expanded="true" data-lm-detail-collapse><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><path d="m6 15 6-6 6 6"/></svg></button></div>
        <div class="lm-detail-offline"><span class="lm-offline-badge">@if($lastSeenStr)Last seen: {{ $lastSeenStr }}{{ $lastSeenDevice ? ' ('.$lastSeenDevice.')' : '' }}@else Never seen online @endif</span><div class="lm-news-time" data-lm-lead-clock data-tz="{{ $leadTz }}" title="The lead's local time ({{ $leadTz }})"><span class="lm-clock-label">Lead's local time</span><strong class="lm-clock-h">--:--</strong><small class="lm-clock-ampm">--</small><b class="lm-clock-date">---</b></div></div>
        @include('admin.leadmax.partials.lead-field-grid')
        <nav class="lm-detail-tabs" aria-label="Lead detail sections">@foreach($tabs as $key => $label)<a class="{{ $tab === $key ? 'active' : '' }}" href="{{ $tabUrl($key) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach<button class="lm-tab-collapse" type="button" aria-label="Collapse section" aria-expanded="true" data-lm-tabs-collapse><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><path d="m6 15 6-6 6 6"/></svg></button></nav><div data-lm-tab-body>
        @if($tab === 'comments')
        @php
            $commentFiles = \App\Models\ActivityLog::query()->where('user_id', $lead->id)->where('type', 'comment_added')->whereNotNull('info->attachment')->get()->keyBy(fn ($log) => (string) ($log->info['note_id'] ?? ''));
            $emojis = ['👍', '👎', '✅', '❌', '📞', '📧', '💰', '📈', '📉', '⏰', '🔥', '⚠️', '🙂', '😀', '🤝', '🙏', '💬', '📝', '⭐', '❗'];
        @endphp
        <section class="lm-detail-comments"><h2>Write your comment:</h2><form data-lm-submit data-lm-comment-form action="{{ route("admin.leadmax.leads.comment", $lead->id) }}" method="POST" enctype="multipart/form-data" data-lm-success="{{ $tabUrl('comments') }}">@csrf<textarea name="note" required maxlength="10000" placeholder="Enter a comment..."></textarea><div class="lm-comment-toolbar"><div class="lm-comment-icons">
            <label class="lm-comment-attach" title="Attach a file (PDF, image, TXT, CSV, DOCX, XLSX, max 10 MB)"><input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.txt,.csv,.docx,.xlsx" data-lm-comment-file hidden><span aria-hidden="true">📎</span><span class="sr-only">Attach file</span></label>
            <div class="lm-action-dropdown" data-lm-action-dropdown><button type="button" aria-label="Insert emoji" title="Emoji" aria-haspopup="true" aria-expanded="false" data-lm-action-trigger="emoji">🙂</button><div class="lm-action-menu lm-comment-popover lm-emoji-grid" data-lm-action-menu="emoji" hidden role="menu">@foreach($emojis as $emoji)<button type="button" role="menuitem" data-lm-action-item data-lm-insert="{{ $emoji }}">{{ $emoji }}</button>@endforeach</div></div>
            <div class="lm-action-dropdown" data-lm-action-dropdown><button type="button" aria-label="Mention a team member" title="Mention" aria-haspopup="true" aria-expanded="false" data-lm-action-trigger="mention">@</button><div class="lm-action-menu lm-comment-popover" data-lm-action-menu="mention" hidden role="menu">@forelse($members as $member)<button type="button" role="menuitem" data-lm-action-item data-lm-insert="{{ '@'.$member->name.' ' }}">{{ $member->name }}</button>@empty<button type="button" role="menuitem" data-lm-action-item disabled>No team members</button>@endforelse</div></div>
            <span class="lm-comment-file" data-lm-comment-file-name hidden></span><button type="button" class="lm-comment-file-clear" data-lm-comment-file-clear aria-label="Remove attachment" hidden>✕</button>
        </div><button class="lm-button primary">+ Add comment</button></div><p role="alert" class="lm-form-error" hidden></p></form><div class="lm-comment-list">@forelse($notes as $note)@php $initial = mb_strtoupper(mb_substr($note->by?->name ?: 'S', 0, 1)); $file = $commentFiles->get((string) $note->id); $canDelete = is_admin() || (string) $note->by_id === (string) auth()->id(); @endphp<article class="lm-comment"><div class="lm-comment-avatar">{{ $initial }}</div><div class="lm-comment-body"><div class="lm-comment-meta"><time>{{ $note->created_at?->format("Y-m-d H:i:s") }}</time><span class="lm-comment-author">{{ $note->by?->name ?: "System" }}</span></div><p>{{ $note->note }}</p>@if($file)<a class="lm-comment-attachment" href="{{ route('admin.leadmax.leads.attachment', [$lead->id, $note->id]) }}">📎 {{ $file->info['filename'] ?? 'Attachment' }}</a>@endif</div><div class="lm-action-dropdown lm-comment-actions" data-lm-action-dropdown><button type="button" class="lm-comment-menu" aria-label="Comment actions" aria-haspopup="true" aria-expanded="false" data-lm-action-trigger="comment-{{ $note->id }}">⋮</button><div class="lm-action-menu lm-comment-popover lm-comment-menu-list" data-lm-action-menu="comment-{{ $note->id }}" hidden role="menu"><button type="button" role="menuitem" data-lm-action-item data-lm-copy-text="{{ $note->note }}">▣ Copy text</button>@if($canDelete)<button type="button" role="menuitem" class="is-danger" data-lm-action-item data-lm-delete="{{ route('admin.leadmax.leads.comment.destroy', [$lead->id, $note->id]) }}" data-lm-confirm="Delete this comment?">🗑 Delete</button>@endif</div></div></article>@empty<p class="lm-empty-state">No comments yet.</p>@endforelse</div></section>
        @else
            @include('admin.leadmax.partials.lead-tabs')
        @endif
        </div>
        {{-- Inside [data-lm-lead-detail], the way the leads list keeps its own dialog: the fragment
             loader looks the dialog up within that element, so a sibling here reads as "not found"
             and reports the lead as gone. --}}
        @if($dialog)
            @include('admin.leadmax.partials.detail-dialog')
        @endif
    </div>
</x-leadmax-layout>
