@php
    $closeUrl = $tabUrl($tab);
    // A file viewer opened from a KYC request returns to that request -- same tab and document
    // -- rather than to the page (NC-23.4). Only a well-formed request id is carried back.
    $kycReturn = $dialog === 'kyc-document' && preg_match('/^[0-9a-f-]{36}$/i', (string) request('return')) ? request('return') : null;
    if ($kycReturn) {
        $closeUrl = $dialogUrl('kyc-request').'&request='.$kycReturn.'&kh_tab='.(in_array(request('return_tab'), ['details', 'documents', 'timeline'], true) ? request('return_tab') : 'documents').'&doc='.urlencode((string) request('document')).'&side='.(request('side') === 'back' ? 'back' : 'front');
    }
    $leadLabel = $lead->name ?: ($lead->account_id ?: substr($lead->id, 0, 8));
    $titles = [
        'assign' => ['Assign to agent', 'Choose an agent for '.$leadLabel.'.'],
        'lead-status' => ['Change lead status', 'Set the call outcome for '.$leadLabel.'.'],
        'change-password' => ['Change Password', 'Set a new password for '.$leadLabel.'.'],
        'delete' => ['Delete lead', 'Permanently remove '.$leadLabel.' and their lead record.'],
        'email' => ['Send email', 'Email '.$leadLabel.' at '.$lead->email.'.'],
        'message' => ['Send in-app message', $leadLabel.' will see this as a notification in the client area.'],
        'payment' => ['Add payment', 'Credit '.$leadLabel.'’s balance through the standard deposit flow.'],
        'refund' => ['Issue refund', 'Debit up to '.format_price($lead->balance).' from '.$leadLabel.'’s balance.'],
        'withdrawal' => ['Issue withdrawal', 'Prepare a withdrawal from '.$leadLabel.'’s account.'],
        'service' => ['Create service request', 'Opens a support ticket for '.$leadLabel.'.'],
        'service-history' => ['Service history', $tickets->count().' service request(s) for '.$leadLabel.'.'],
        'cancel-service' => ['Cancel service request', 'Close an open service request for '.$leadLabel.'.'],
        'invoice' => ['Create invoice', 'Bill '.$leadLabel.'. Invoices are records only and do not change the client balance.'],
        'credit-note' => ['Create credit note', 'Credit '.$leadLabel.', optionally against an existing invoice.'],
        'escalate' => ['Escalate issue', 'Marks the request urgent and moves it to in progress.'],
        'assign-support' => ['Assign to support agent', 'Choose who handles the request.'],
        'ticket-priority' => ['Change priority', 'Set the priority of an open service request.'],
        'signal' => ['Send signal', 'Share a trade idea with '.$leadLabel.'. It is logged on the Signals tab.'],
        'adjustment' => ['Adjust balance', 'Add to or deduct from '.$leadLabel.'’s Spot wallet. Kept out of deposits and the client’s history.'],
        'history-event' => [$historyEvent['title'] ?? 'Event details', $historyEvent ? $historyEvent['at']->format('Y-m-d H:i:s').' · '.$leadLabel : 'This event is not available.'],
        // NC-24.4: the file, its version and the request it belongs to.
        'kyc-document' => ['Document details', $kycDocument ? ($kycDocument['name'] ?: 'Unnamed file').($kycDocument['version'] ? ' · version '.$kycDocument['version'] : '').($kycDocument['reference'] ? ' · KYC request '.$kycDocument['reference'] : '') : 'This document is not available.'],
        'kyc-new' => ['New KYC', $leadLabel.($lead->account_id ? ' · Account '.$lead->account_id : '')],
        'kyc-notes' => ['KYC notes',$kycNotes ? 'Attempt '.$kycNotes['header']->attempt.' · '.$kycNotes['header']->label().' · '.$leadLabel : 'This submission is not available.'],
        // NC-24.1: line 1 is the reference (its state pill sits beside it), line 2 the client and their verification.
        'kyc-request' => [$kycRequest ? 'KYC Request · '.$kycRequest['reference'] : 'KYC request', $kycRequest ? $leadLabel.($lead->account_id ? ' · Account '.$lead->account_id : '').' · Client verification: '.ucfirst(strtolower((string) $lead->verification_status) ?: 'not verified') : 'This request is not available for this lead.'],
        'signal-close' => ['Close signal',$signalToClose ? $signalToClose->code().' · '.strtoupper($signalToClose->direction).' '.$signalToClose->instrument : 'This signal is no longer available.'],
    ];
    $openInvoices = $invoices->where('type', 'invoice')->where('status', '!=', 'void');

    [$title, $subtitle] = $titles[$dialog];
@endphp
<div class="lm-modal-backdrop {{ in_array($dialog, ['kyc-request', 'kyc-document'], true) ? 'lm-kyc-request-backdrop' : '' }}" data-lm-dismiss-url="{{ $closeUrl }}" @if($kycReturn) data-lm-return-url="{{ $closeUrl }}" @endif>
<section role="dialog" aria-modal="true" aria-labelledby="lm-dialog-title" tabindex="-1" class="lm-dialog lm-detail-dialog {{ in_array($dialog, ['invoice', 'credit-note', 'kyc-document', 'kyc-new'], true) ? 'lm-detail-dialog-wide' : '' }} {{ $dialog === 'kyc-request' ? 'lm-kyc-request-dialog' : '' }} {{ $dialog === 'kyc-document' ? 'lm-kyc-viewer-dialog'.(request('expand') ? ' is-expanded' : '') : '' }} {{ $dialog === 'assign' ? 'lm-owner-dialog' : '' }}" data-lm-dialog>
    <header class="lm-dialog-heading"><div><h2 id="lm-dialog-title">{{ $title }}</h2><p>{{ $subtitle }}</p></div>@if($dialog === 'kyc-document' && $kycDocument)<span class="lm-kyc-readonly-badge">🔒 Read-only</span>@endif @if($dialog === 'kyc-request' && $kycRequest)<span class="lm-kyc-state lm-kyc-state-{{ $kycRequest['state'] }} lm-kyc-head-state">{{ $kycRequest['stateLabel'] }}</span>@if($kycRequest['legacy'])<span class="lm-kyc-state lm-kyc-state-legacy lm-kyc-head-legacy">Legacy request</span>@endif @endif<a class="lm-close" href="{{ $closeUrl }}" aria-label="{{ $kycReturn ? 'Close and return to the KYC request' : 'Close dialog' }}"><x-leadmax-lead-icon name="close" /></a></header>
@if($dialog === 'assign')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.assign', $lead->id) }}" method="POST" data-lm-success="{{ $closeUrl }}">@csrf
    <div class="lm-owner-content"><input data-lm-member-search aria-label="Search team members" placeholder="Search team members..."><p>TEAM MEMBERS</p><div class="lm-members">@foreach($members as $member)<label class="lm-member" data-lm-member="{{ strtolower($member->name) }}"><span class="lm-initials">{{ collect(preg_split('/\s+/', trim($member->name)))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}<i class="{{ $member->is_online ? 'online' : '' }}"></i></span><span><strong>{{ $member->name }}</strong><small>{{ $member->label ?: ($member->roles->first()?->display_name ?: 'Team member') }}</small></span><input type="radio" required name="manager_id" value="{{ $member->id }}" @checked(($lead->manager_id ?: $members->first()?->id) === $member->id)></label>@endforeach</div><button type="button" class="lm-button lm-all-members" data-lm-all-members><x-leadmax-lead-icon name="team" />View all team members</button><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary"><x-leadmax-lead-icon name="assign" />Assign agent</button></footer></form>
@elseif($dialog === 'lead-status')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.lead-status', $lead->id) }}" method="POST" data-lm-success="{{ $closeUrl }}">@csrf
    <div class="lm-detail-dialog-body"><label>Lead status<select name="lead_status" required><option value="{{ \App\Http\Controllers\Admin\LeadMaxLeadController::LEAD_STATUS_NONE }}" @selected(blank($lead->label))>Not set</option>@foreach($leadStatuses as $option)<option value="{{ $option }}" @selected($lead->label === $option)>{{ ucfirst($option) }}</option>@endforeach</select></label><label>Note <span>(optional)</span><textarea name="note" maxlength="500" rows="4" placeholder="Reason or context for this status change"></textarea></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Update lead status</button></footer></form>
@elseif($dialog === 'change-password')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.change-password', $lead->id) }}" method="POST" data-lm-success="{{ $closeUrl }}">@csrf
    <div class="lm-detail-dialog-body"><label>New Password<input name="password" type="password" required minlength="6" maxlength="64" autocomplete="new-password" placeholder="Enter new password"></label><label>Confirm Password<input name="password_confirmation" type="password" required minlength="6" maxlength="64" autocomplete="new-password" placeholder="Confirm new password"></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}"><x-leadmax-lead-icon name="close" />Cancel</a><button class="lm-button primary">Update Password</button></footer></form>
@elseif($dialog === 'delete')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.destroy', $lead->id) }}" method="POST" data-lm-success="{{ route('admin.leadmax.leads') }}">@csrf @method('DELETE')
    <div class="lm-detail-dialog-body"><p>This action cannot be undone. Enter your password to confirm.</p><label>Your password<input name="password" type="password" required autocomplete="current-password" placeholder="Enter your password"></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button danger">Delete lead</button></footer></form>
@elseif($dialog === 'email')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.email', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('history') }}">@csrf
    <div class="lm-detail-dialog-body"><label>Subject<input name="subject" required maxlength="200" placeholder="e.g. Your account is ready"></label><label>Message<textarea name="body" required maxlength="10000" rows="8" placeholder="Write your message..."></textarea></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Send email</button></footer></form>
@elseif($dialog === 'message')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.message', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('history') }}">@csrf
    <div class="lm-detail-dialog-body"><label>Message<textarea name="message" required maxlength="2000" rows="5" placeholder="Write the popup message..."></textarea></label><label>Style<select name="color"><option value="info">Information</option><option value="success">Success</option><option value="warning">Warning</option><option value="danger">Alert</option></select></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Send message</button></footer></form>
@elseif($dialog === 'withdrawal')
    @php
        $withdrawalAccounts = $lead->tradingAccounts()->orderBy('created_at')->get();
        $withdrawalSources = $withdrawalAccounts->map(fn ($account) => ['trading_account:'.$account->id, 'Trading account #'.$account->account_id.' ('.ucfirst($account->account_type ?: 'live').') · free '.format_price($account->withdrawableBalance())]);
        if ((float) $lead->balance > 0 || $withdrawalAccounts->isEmpty()) {
            $withdrawalSources->push(['spot_wallet', 'Spot Wallet · '.format_price((float) $lead->balance)]);
        }
    @endphp
    <form data-lm-submit action="{{ route('admin.leadmax.leads.withdrawal', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('finances') }}">@csrf
    <div class="lm-detail-dialog-body">
        <label>Amount (USD)<input name="amount" type="number" required min="0.01" step="0.01" inputmode="decimal" placeholder="0.00"></label>
        <label>Withdraw from<select name="source" required>@foreach($withdrawalSources as [$value, $label])<option value="{{ $value }}" @selected($loop->first)>{{ $label }}</option>@endforeach</select></label>
        <label>Reference / note <span class="lm-optional">Optional</span><textarea name="note" maxlength="500" rows="3" placeholder="Reason or internal reference for this withdrawal"></textarea></label>
        <label>Notify the client<select name="notify"><option value="no">No</option><option value="yes">Yes, show a notification</option></select></label>
        <p class="lm-optional">Only free funds can be withdrawn; margin held by open trades stays in the account. The withdrawal is recorded as an approved transaction.</p>
        <p role="alert" class="lm-form-error" hidden></p>
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button danger">Issue withdrawal</button></footer></form>
@elseif(in_array($dialog, ['payment', 'refund'], true))
    <form data-lm-submit action="{{ route('admin.leadmax.leads.payment', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('finances') }}">@csrf<input type="hidden" name="type" value="{{ $dialog === 'refund' ? 'withdraw' : 'deposit' }}">
    <div class="lm-detail-dialog-body"><label>Amount<input name="amount" type="number" required min="0.01" step="0.01" @if($dialog === 'refund') max="{{ (float) $lead->balance }}" @endif placeholder="0.00"></label>
        {{-- The same destinations the central panel offers: the lead's trading accounts, then the
             Credit, Bonus and Spot wallets. accounts() builds both lists. --}}
        <label>{{ $dialog === 'refund' ? 'Take it from' : 'Add it to' }}<select name="account" required>@foreach(accounts($lead) as $value => $label)<option value="{{ $value }}" @selected($value === request('account', 'spot_wallet'))>{{ $label }}</option>@endforeach</select></label>
        <label>Description<input name="description" maxlength="500" placeholder="{{ $dialog === 'refund' ? 'Reason for the refund' : 'e.g. Bank transfer received' }}"></label><label>Notify the client<select name="notify"><option value="no">No</option><option value="yes">Yes, show a notification</option></select></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button {{ $dialog === 'refund' ? 'danger' : 'primary' }}">{{ $dialog === 'refund' ? 'Issue refund' : 'Add payment' }}</button></footer></form>
@elseif($dialog === 'service')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.service', $lead->id) }}" method="POST" data-lm-success="{{ $dialogUrl('service-history') }}">@csrf
    <div class="lm-detail-dialog-body"><label>Category<select name="category" required>@foreach(\App\Models\SupportTicket::CATEGORIES as $category)<option value="{{ $category }}">{{ ucfirst(str_replace('_', ' ', $category)) }}</option>@endforeach</select></label><label>Subject<input name="subject" required maxlength="200" placeholder="Short summary of the request"></label><label>Details<textarea name="message" required maxlength="5000" rows="5" placeholder="Describe the request..."></textarea></label><p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Create request</button></footer></form>
@elseif($dialog === 'adjustment')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.adjustment', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('finances') }}">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <div class="lm-detail-dialog-body" data-lm-adjust data-balance="{{ number_format((float) $lead->balance, 2, '.', '') }}">
        <p>Current Spot wallet balance: <strong>{{ format_price($lead->balance) }}</strong></p>
        <label>Adjustment amount<input name="amount" type="text" inputmode="decimal" required pattern="[+\-]?\d{1,9}(\.\d{1,2})?" placeholder="Enter the amount (negative amounts allowed)" autocomplete="off" data-lm-adjust-amount></label>
        <p>New balance after this adjustment: <strong data-lm-adjust-preview aria-live="polite">—</strong></p>
        <label>Reason<textarea name="reason" required minlength="3" maxlength="500" rows="3" placeholder="Why the balance is being adjusted"></textarea></label>
        <p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Apply adjustment</button></footer></form>
@elseif($dialog === 'history-event')
    {{-- Read-only: everything recorded about one history row. Closing returns to the same tab and page. --}}
    <div class="lm-detail-dialog-body">@if($historyEvent)<dl class="lm-history-detail">@foreach($historyEvent['fields'] as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach</dl>@else<p class="lm-empty-state">This event is not available.</p>@endif</div>
    <footer class="lm-dialog-footer"><a class="lm-button primary" href="{{ $closeUrl }}">Close</a></footer>
@elseif($dialog === 'kyc-document')
    {{-- NC-23.4, frame 4228:30862: a read-only "Document details" viewer. The real stored file on
         the left (zoom, Fit to width, full view), the request's recorded facts on the right. It
         has no Accept/Deny, Approve/Reject or status control: previewing, downloading or closing
         never claims a review, marks a document reviewed or changes a verification. Files only ever
         come through the authenticated stream route. --}}
    @if($kycDocument)
        @php
            $kycDocUrl = fn (string $side) => $dialogUrl('kyc-document').'&document='.$kycDocument['document']->id.'&side='.$side
                .($kycReturn ? '&return='.$kycReturn.'&return_tab='.urlencode((string) request('return_tab', 'documents')) : '');
            [$docStateKey, $docStateLabel] = match ($kycDocument['status']) {
                'approved' => ['approved', 'Approved'], 'rejected' => ['rejected', 'Rejected'], default => ['pending_approval', 'Awaiting review'],
            };
        @endphp
        <div class="lm-detail-dialog-body lm-kyc-viewer-body">
            <section class="lm-kyc-file" aria-label="File preview">
                <div class="lm-kyc-file-bar">
                    <span>File preview <small>{{ $kycDocument['fileType'] }}</small></span>
                    @if($kycDocument['sides'] && $kycDocument['available'])
                        <span class="lm-kyc-zoom" role="group" aria-label="Zoom">
                            <button type="button" class="lm-button" data-lm-kyc-zoom="-25" aria-label="Zoom out">−</button>
                            <output data-lm-kyc-zoom-value aria-live="polite">100%</output>
                            <button type="button" class="lm-button" data-lm-kyc-zoom="25" aria-label="Zoom in">+</button>
                            <button type="button" class="lm-button" data-lm-kyc-fit aria-pressed="true">Fit to width</button>
                        </span>
                        @if($kycDocument['pages'] > 1)
                            {{-- Real PDF pages; changing page keeps the zoom (NC-24.4). --}}
                            <span class="lm-kyc-pages" role="group" aria-label="Pages" data-lm-kyc-pages="{{ $kycDocument['pages'] }}">
                                <button type="button" class="lm-button" data-lm-kyc-page="-1" aria-label="Previous page" disabled>‹</button>
                                <output data-lm-kyc-page-value aria-live="polite">Page 1 of {{ $kycDocument['pages'] }}</output>
                                <button type="button" class="lm-button" data-lm-kyc-page="1" aria-label="Next page">›</button>
                            </span>
                        @endif
                        <button type="button" class="lm-button lm-kyc-expand" data-lm-kyc-expand aria-pressed="{{ request('expand') ? 'true' : 'false' }}" aria-label="Expand the file to the whole window">⤢</button>
                        <a class="lm-button" href="{{ $kycDocument['previewUrl'] }}" target="_blank" rel="noopener" aria-label="Open the file in a new tab">↗</a>
                    @endif
                </div>
                @if(count($kycDocument['sides']) > 1)
                    <div class="lm-kyc-sides" role="group" aria-label="Side">@foreach($kycDocument['sides'] as $side)<a href="{{ $kycDocUrl($side) }}" @class(['lm-button', 'primary' => $kycDocument['side'] === $side]) @if($kycDocument['side'] === $side) aria-current="true" @endif>{{ ucfirst($side) }}</a>@endforeach</div>
                @endif
                <div class="lm-kyc-file-stage" data-lm-kyc-stage-view data-fit="1">
                    @if(! $kycDocument['sides'] || ! $kycDocument['available'])
                        <p class="lm-empty-state">The stored file is unavailable, so it cannot be shown. No other file is shown in its place.</p>
                    @elseif($kycDocument['isPdf'])
                        <object data="{{ $kycDocument['previewUrl'] }}#view=FitH" data-base="{{ $kycDocument['previewUrl'] }}" type="application/pdf" class="lm-kyc-file-frame" aria-label="{{ $kycDocument['name'] }} (PDF)"><p class="lm-empty-state">This PDF cannot be shown here. Use Download file.</p></object>
                    @else
                        <img src="{{ $kycDocument['previewUrl'] }}" alt="{{ ucfirst($kycDocument['side']) }} of {{ $kycDocument['name'] }}" class="lm-kyc-file-frame" data-lm-kyc-zoom-target>
                    @endif
                </div>
            </section>
            <section class="lm-kyc-facts-panel" aria-labelledby="lm-kyc-review-status">
                <h3 id="lm-kyc-review-status">Review status</h3>
                <span class="lm-kyc-state lm-kyc-state-{{ $docStateKey }}">{{ $docStateLabel }}</span>
                <p class="lm-kyc-hint">{{ $kycDocument['reference'] ? 'KYC request '.$kycDocument['reference'] : 'Past KYC Document History' }}</p>
                <dl class="lm-kyc-facts-list">
                    @foreach($kycDocument['fields'] as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>@if($label === 'Issuing Country' && $kycDocument['countryCode'])<x-leadmax-flag :country="$kycDocument['countryCode']" /> @endif{{ filled($value) ? $value : 'Not recorded' }}</dd></div>
                    @endforeach
                    @foreach($kycDocument['details'] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
                </dl>
                <p class="lm-kyc-lock-note">🔒 {{ $kycDocument['historical'] ? 'Historical document. Review status cannot be changed here.' : 'View only. This document is reviewed and decided in its KYC request.' }}</p>
                <details class="lm-kyc-trail">
                    <summary>Verification audit trail ({{ $kycDocument['trail']->count() }})</summary>
                    @forelse($kycDocument['trail'] as $entry)
                        <p><strong>{{ $entry['label'] }}</strong> · {{ $entry['actor'] }}{{ $entry['role'] ? ' ('.$entry['role'].')' : '' }} · {{ $entry['at']?->format('Y-m-d H:i') }}@if($entry['note'])<br><span>{{ $entry['note'] }}</span>@endif</p>
                    @empty
                        <p class="lm-empty-state">No recorded events for this document. It was uploaded before the audit trail existed.</p>
                    @endforelse
                </details>
            </section>
        </div>
        <footer class="lm-dialog-footer lm-kyc-viewer-footer">
            <span class="lm-kyc-hint">{{ $kycDocument['historical'] ? 'Historical record · View only' : 'View only' }}</span>
            <a class="lm-button" href="{{ $closeUrl }}" data-lm-kyc-viewer-close>Close</a>
            @if($kycDocument['sides'] && $kycDocument['available'])<a class="lm-button primary" href="{{ $kycDocument['downloadUrl'] }}" download>Download file</a>@endif
        </footer>
    @else
        <div class="lm-detail-dialog-body"><p class="lm-empty-state">This document is not available.</p></div>
        <footer class="lm-dialog-footer"><a class="lm-button primary" href="{{ $closeUrl }}">Close</a></footer>
    @endif
@elseif($dialog === 'kyc-new')
    {{-- NC-13/14/15 wizard (frames 3095:28883, 29093, 29305, 29497). Each Continue saves the step
         into a draft; the Address step submits. Review is the receipt of what was submitted. --}}
    @php
        $w = $kycWizard;
        $wizardSteps = ['personal' => 'Personal Info', 'identity' => 'Identity', 'address' => 'Address', 'review' => 'Review'];
        $wizardProgress = ['personal' => 0, 'identity' => 25, 'address' => 50][$w['step']] ?? 0;
    @endphp
    @if($w['blocked'])
        <div class="lm-detail-dialog-body"><p class="lm-empty-state">A KYC submission is already awaiting review. Finish that review before starting another.</p></div>
        <footer class="lm-dialog-footer"><a class="lm-button primary" href="{{ $closeUrl }}">Close</a></footer>
    @else
    <form class="lm-kyc-wizard" data-lm-kyc-wizard action="{{ route('admin.leadmax.leads.kyc.draft', $lead->id) }}" data-submit-url="{{ route('admin.leadmax.leads.kyc.submit', $lead->id) }}" data-version="{{ $w['version'] }}" data-step="{{ $w['step'] }}" data-document-url="{{ $dialogUrl('kyc-document') }}&side=front&document=" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="lm-detail-dialog-body">
            <ol class="lm-kyc-stages">@foreach($wizardSteps as $key => $label)<li data-lm-kyc-stage="{{ $key }}" @if($key === $w['step']) aria-current="step" @endif>{{ $label }}</li>@endforeach</ol>
            <div class="lm-kyc-progress"><progress max="100" value="{{ $wizardProgress }}" data-lm-kyc-progress aria-label="KYC process progress"></progress><span data-lm-kyc-progress-label>{{ $wizardProgress }}% of the process complete</span></div>

            <section data-lm-kyc-step="personal" @if($w['step'] !== 'personal') hidden @endif>
                <h3>Personal Information</h3>
                <p>Verify your personal information matches your government-issued ID.</p>
                <div class="lm-kyc-grid">
                    <label>Legal First Name<input name="first_name" required maxlength="100" value="{{ $w['personal']['first_name'] }}" autocomplete="off"></label>
                    <div class="lm-kyc-last-name">
                        <label>Legal Last Name<input name="last_name" required maxlength="100" value="{{ $w['personal']['last_name'] }}" autocomplete="off" @disabled($w['personal']['single_name'])></label>
                        <label class="lm-kyc-single"><input type="checkbox" name="single_name" value="1" data-lm-kyc-single @checked($w['personal']['single_name'])> Single legal name (no last name)</label>
                    </div>
                    <label>Date of Birth<input name="birthdate" type="date" required max="{{ now()->toDateString() }}" value="{{ $w['personal']['birthdate'] }}"></label>
                    <label>Nationality<select name="nationality" required><option value="">Select nationality</option>@foreach($w['countries'] as $country)<option value="{{ $country }}" @selected($w['personal']['nationality'] === $country)>{{ $country }}</option>@endforeach</select></label>
                    <div class="lm-kyc-phone"><label>Phone Number<span class="lm-kyc-phone-row"><select name="dial_code" required aria-label="Dial code"><option value="">Code</option>@foreach($w['dialCodes'] as $dial)<option value="{{ $dial }}" @selected($w['personal']['dial_code'] === $dial)>{{ $dial }}</option>@endforeach</select><input name="phone" required inputmode="tel" maxlength="20" value="{{ $w['personal']['phone'] }}" aria-label="Phone number"></span></label><small>Use the phone number associated with this lead.</small></div>
                </div>
                <p class="lm-kyc-callout">Please ensure the information matches your government-issued ID. Discrepancies may delay the verification process.</p>
            </section>

            <section data-lm-kyc-step="identity" @if($w['step'] !== 'identity') hidden @endif>
                <h3>Identity Verification</h3>
                <p>Please upload a valid government-issued identification document.</p>
                <fieldset class="lm-kyc-cards"><legend>SELECT ID TYPE</legend>
                    @foreach(\App\Models\KycSubmission::ID_TYPES as $value => $type)<label class="lm-kyc-card"><input type="radio" name="id_type" value="{{ $value }}" required data-lm-kyc-id-type @checked(($w['id_type'] ?? 'passport') === $value)><span>{{ $type['label'] }}</span></label>@endforeach
                </fieldset>
                <div class="lm-kyc-uploads">
                    <label class="lm-kyc-drop">Front side<input type="file" name="id_front" accept=".jpg,.jpeg,.png,.pdf" data-lm-kyc-file-input><small data-lm-file-name>{{ $w['files']['id_front'] ? 'Uploaded: '.$w['files']['id_front'] : 'Drag and drop or browse · JPG, PNG or PDF, max 5 MiB' }}</small></label>
                    <label class="lm-kyc-drop">Back side <span class="lm-optional">Optional</span><input type="file" name="id_back" accept=".jpg,.jpeg,.png,.pdf" data-lm-kyc-file-input><small data-lm-file-name>{{ $w['files']['id_back'] ?: 'Only if the document has a back · JPG, PNG or PDF, max 5 MiB' }}</small></label>
                </div>
                {{-- NC-23.1: optional document details; leave blank when not to hand. --}}
                <div class="lm-kyc-grid">
                    {{-- "Other": staff type the document's name, which becomes its label in the history. --}}
                    <label data-lm-kyc-id-other @if(($w['id_type'] ?? 'passport') !== 'other') hidden @endif>Document Name<input name="id_type_other" minlength="2" maxlength="100" value="{{ $w['id_type_other'] }}" autocomplete="off" @if(($w['id_type'] ?? 'passport') === 'other') required @endif></label>
                    <label>Document Number <span class="lm-optional">Optional</span><input name="document_number" maxlength="50" value="{{ $w['metadata']['document_number'] }}" autocomplete="off"></label>
                    <label>Expiry Date <span class="lm-optional">Optional</span><input name="document_expiry" type="date" value="{{ $w['metadata']['document_expiry'] }}"></label>
                    <label>Issuing Country <span class="lm-optional">Optional</span><select name="issuing_country"><option value="">Not recorded</option>@foreach($w['countries'] as $country)<option value="{{ $country }}" @selected($w['metadata']['issuing_country'] === $country)>{{ $country }}</option>@endforeach</select></label>
                </div>
            </section>

            <section data-lm-kyc-step="address" @if($w['step'] !== 'address') hidden @endif>
                <h3>Proof of Address</h3>
                <div class="lm-kyc-stack">
                    <label>Street Address<input name="street" required maxlength="200" value="{{ $w['address']['street'] }}"></label>
                    <label>City<input name="city" required maxlength="100" value="{{ $w['address']['city'] }}"></label>
                    <label>Postcode<input name="postcode" maxlength="20" value="{{ $w['address']['postcode'] }}"></label>
                    <label>Country<select name="country" required><option value="">Select country</option>@foreach($w['countries'] as $country)<option value="{{ $country }}" @selected($w['address']['country'] === $country)>{{ $country }}</option>@endforeach</select></label>
                    <label>Document Type<select name="document_type" required data-lm-kyc-doc-type>@foreach(\App\Models\KycSubmission::ADDRESS_TYPES as $value => $label)<option value="{{ $value }}" @selected($w['address']['document_type'] === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label data-lm-kyc-other @if($w['address']['document_type'] !== 'others') hidden @endif>Specify document type<input name="document_type_other" minlength="2" maxlength="100" value="{{ $w['address']['document_type_other'] }}"></label>
                    <div class="lm-kyc-uploads">
                        <label class="lm-kyc-drop">Front / first page<input type="file" name="address_front" accept=".jpg,.jpeg,.png,.pdf" data-lm-kyc-file-input><small data-lm-file-name>{{ $w['files']['address_front'] ? 'Uploaded: '.$w['files']['address_front'] : 'Drag and drop or browse · JPG, PNG or PDF, max 5 MiB' }}</small></label>
                        <label class="lm-kyc-drop">Back / additional page <span class="lm-optional">Optional</span><input type="file" name="address_back" accept=".jpg,.jpeg,.png,.pdf" data-lm-kyc-file-input><small data-lm-file-name>{{ $w['files']['address_back'] ?: 'Only if the document has more pages' }}</small></label>
                    </div>
                </div>
                <p class="lm-kyc-hint">Submitting approves this KYC straight away: staff check documents before adding them.</p>
            </section>

            <section data-lm-kyc-step="review" hidden aria-live="polite">
                <div class="lm-kyc-receipt">
                    <span class="lm-kyc-receipt-mark" aria-hidden="true">✓</span>
                    <h3>KYC submitted — <span data-lm-kyc-receipt-state>Approved</span></h3>
                    <dl class="lm-history-detail"><dt>Status</dt><dd data-lm-kyc-receipt-label>Approved</dd><dt>Submitted by</dt><dd data-lm-kyc-receipt-by></dd><dt>Submitted at</dt><dd data-lm-kyc-receipt-at></dd></dl>
                    <p data-lm-kyc-receipt-email hidden>The client has been emailed that their verification is approved.</p>
                </div>
            </section>
            <p role="alert" class="lm-form-error" hidden></p>
        </div>
        <footer class="lm-dialog-footer" data-lm-kyc-actions>
            <a class="lm-button" href="{{ $closeUrl }}" data-lm-kyc-cancel>Cancel process</a>
            <button type="button" class="lm-button" data-lm-kyc-back @if($w['step'] === 'personal') hidden @endif>Back</button>
            <button type="button" class="lm-button primary" data-lm-kyc-next>{{ $w['step'] === 'address' ? 'Submit KYC' : 'Continue' }}</button>
        </footer>
        <footer class="lm-dialog-footer" data-lm-kyc-done hidden>
            <a class="lm-button" href="{{ $tabUrl('kyc') }}" data-lm-kyc-refresh>Back to KYC</a>
            <a class="lm-button primary" href="{{ $tabUrl('kyc') }}" data-lm-kyc-refresh data-lm-kyc-view>View submission</a>
        </footer>
    </form>
    @endif
@elseif($dialog === 'kyc-request')
    {{-- NC-23.4/23.5: one KYC request and its whole bundle. Opening it, switching tabs or previewing
         a file is read-only; Start Review, Mark reviewed, Accept and Deny are explicit actions, and
         Accept/Deny act on the entire request from the footer, never on one file. --}}
    @if($kycRequest)
        @php
            $r = $kycRequest;
            $h = $r['header'];
            $selfUrl = $dialogUrl('kyc-request').'&request='.$h->id;
            // One KYC, no levels (10 Oct 2026): the client's own status, apart from this request.
            $clientStatus = ucfirst(strtolower((string) $lead->verification_status) ?: 'not verified');
            // The Details section to show: Request, unless a form returned to another one.
            $detailsSection = in_array(request('kh_section'), ['request', 'personal', 'address', 'requirements', 'documents', 'notes', 'update'], true) ? request('kh_section') : 'request';
            $acceptCopy = 'Accept '.$r['reference'].'? The client’s KYC will be approved.';
            $source = ['client' => 'Client upload', 'crm' => 'Staff submission (approved on submission)', 'legacy_backfill' => 'Recorded before KYC requests existed'][$h->source] ?? ucfirst((string) $h->source);
        @endphp
        <div class="lm-kyc-request" data-lm-kyc-request-modal data-request="{{ $h->id }}" data-version="{{ $h->version }}"
             data-key="{{ \Illuminate\Support\Str::uuid() }}" data-self-url="{{ $selfUrl }}" data-decision-url="{{ route('admin.leadmax.leads.kyc.request.decision', ['id' => $lead->id, 'submission' => $h->id]) }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-lm-kyc-token>
            {{-- NC-24.1: the request's secondary facts are in Details, so the documents get the space. --}}
            <div class="lm-kyc-request-tabs" role="tablist" aria-label="Request sections">
                @foreach(['details' => 'Details', 'documents' => 'Documents', 'timeline' => 'Timeline'] as $panelKey => $panelLabel)
                    <button type="button" role="tab" id="lm-kyc-tab-{{ $panelKey }}" aria-controls="lm-kyc-panel-{{ $panelKey }}" aria-selected="{{ $r['tab'] === $panelKey ? 'true' : 'false' }}" tabindex="{{ $r['tab'] === $panelKey ? '0' : '-1' }}" data-lm-kyc-tab="{{ $panelKey }}">{{ $panelLabel }}</button>
                @endforeach
            </div>
            <div class="lm-kyc-request-body">
                <section role="tabpanel" id="lm-kyc-panel-documents" aria-labelledby="lm-kyc-tab-documents" data-lm-kyc-panel="documents" @if($r['tab'] !== 'documents') hidden @endif>
                    @if($r['documents']->isEmpty())
                        <p class="lm-empty-state">No documents are attached to this request.</p>
                    @else
                        <div class="lm-kyc-workspace">
                            {{-- NC-24.1: a short body gets this labelled selector instead of the tall card list; both select the same document. --}}
                            <div class="lm-kyc-docbar">
                                <label for="lm-kyc-doc-select">Document</label>
                                <select id="lm-kyc-doc-select" data-lm-kyc-doc-select aria-describedby="lm-kyc-bundle-count">
                                    @foreach($r['documents'] as $doc)
                                        @php($optionBase = $doc['category'].' · '.$doc['label'].' · '.\App\Services\LeadMax\LeadDetailTabs::kycCount($doc['sides']->count(), 'file'))
                                        @php($optionWarn = $doc['sides']->contains('available', false) ? ' · File unavailable' : '')
                                        <option value="{{ $doc['id'] }}" data-base="{{ $optionBase }}" data-warn="{{ $optionWarn }}" @selected($r['selected']['id'] === $doc['id'])>{{ $optionBase }} · {{ $doc['acknowledged'] ? 'Reviewed' : 'Not reviewed' }}{{ $optionWarn }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lm-kyc-doclist" aria-label="Documents in this request">
                                <p class="lm-kyc-bundle-count" id="lm-kyc-bundle-count" data-lm-kyc-bundle>{{ $r['bundle']['progress'] }}</p>
                                @foreach($r['documents']->groupBy('category') as $category => $items)
                                    <p class="lm-kyc-doclist-group">{{ $category }}</p>
                                    @foreach($items as $doc)
                                        <button type="button" class="lm-kyc-docitem" data-lm-kyc-doc="{{ $doc['id'] }}" aria-controls="lm-kyc-view-{{ $doc['id'] }}" aria-pressed="{{ $r['selected']['id'] === $doc['id'] ? 'true' : 'false' }}">
                                            <strong>{{ $doc['label'] }}</strong>
                                            <small>{{ \App\Services\LeadMax\LeadDetailTabs::kycCount($doc['sides']->count(), 'file') }} · {{ $doc['uploaded']?->format('Y-m-d') }}</small>
                                            <span class="lm-kyc-docitem-tags">
                                                <span class="lm-chip">{{ $doc['required'] ? 'Required' : 'Supporting' }}</span>
                                                @if($doc['sides']->contains('available', false))<span class="lm-chip is-warn">File unavailable</span>@endif
                                                <span class="lm-chip {{ $doc['acknowledged'] ? 'is-ok' : '' }}" data-lm-kyc-ack-chip="{{ $doc['id'] }}">{{ $doc['acknowledged'] ? 'Reviewed ✓' : 'Not reviewed' }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                @endforeach
                            </div>
                            <div class="lm-kyc-viewer">
                                @foreach($r['documents'] as $doc)
                                    <div class="lm-kyc-docview" id="lm-kyc-view-{{ $doc['id'] }}" data-lm-kyc-docview="{{ $doc['id'] }}" @if($r['selected']['id'] !== $doc['id']) hidden @endif>
                                        <div class="lm-kyc-docview-head">
                                            <h3>{{ $doc['label'] }}</h3>
                                            <span class="is-muted">{{ $doc['type'] }}{{ $doc['version'] ? ' · version '.$doc['version'] : '' }}</span>
                                        </div>
                                        @php($openSide = $r['selected']['id'] === $doc['id'] && $r['side'] && $doc['sides']->contains('side', $r['side']) ? $r['side'] : ($doc['sides']->first()['side'] ?? null))
                                        @if($doc['sides']->count() > 1)
                                            <div class="lm-kyc-sides" role="group" aria-label="Side of {{ $doc['label'] }}">
                                                @foreach($doc['sides'] as $sideIndex => $side)<button type="button" class="lm-button {{ $side['side'] === $openSide ? 'primary' : '' }}" data-lm-kyc-side="{{ $side['side'] }}" aria-pressed="{{ $side['side'] === $openSide ? 'true' : 'false' }}">{{ ucfirst($side['side']) }}{{ $side['pdf'] ? ' (PDF)' : '' }}</button>@endforeach
                                            </div>
                                        @endif
                                        @forelse($doc['sides'] as $sideIndex => $side)
                                            <figure class="lm-kyc-sideview" data-lm-kyc-sideview="{{ $side['side'] }}" @if($side['side'] !== $openSide) hidden @endif>
                                                @if(! $side['available'])
                                                    <p class="lm-empty-state">The {{ $side['side'] }} of this document is no longer in storage, so it cannot be shown{{ $doc['required'] ? ' and the request cannot be accepted' : '' }}.</p>
                                                @elseif($side['pdf'])
                                                    <object data="{{ $side['preview'] }}" type="application/pdf" class="lm-kyc-preview-frame" aria-label="{{ ucfirst($side['side']) }} of {{ $doc['label'] }} (PDF)"><p class="lm-empty-state">This PDF cannot be shown here. Use View Full Document.</p></object>
                                                @else
                                                    <img src="{{ $side['preview'] }}" alt="{{ ucfirst($side['side']) }} of {{ $doc['label'] }}" class="lm-kyc-preview-frame" loading="lazy">
                                                @endif
                                                @if($side['available'])
                                                    {{-- View Full Document opens the read-only file viewer nested in this request; its Close comes back here. --}}
                                                    <figcaption>{{ ucfirst($side['side']) }}{{ $side['pdf'] ? ' · PDF' : '' }} · <a class="is-link" href="{{ $dialogUrl('kyc-document') }}&document={{ $doc['id'] }}&side={{ $side['side'] }}&return={{ $h->id }}&return_tab=documents" data-lm-kyc-view-file>View Full Document</a> · <a class="is-link" href="{{ $dialogUrl('kyc-document') }}&document={{ $doc['id'] }}&side={{ $side['side'] }}&return={{ $h->id }}&return_tab=documents&expand=1" data-lm-kyc-view-file>Expand document</a> · <a class="is-link" href="{{ $side['download'] }}" download>Download</a></figcaption>
                                                @endif
                                            </figure>
                                        @empty
                                            <p class="lm-empty-state">No file was uploaded for this document.</p>
                                        @endforelse
                                        <dl class="lm-history-detail lm-kyc-docmeta">
                                            <dt>Document number</dt><dd>{!! $doc['number_last4'] ? '<span aria-label="Ending in '.e($doc['number_last4']).'">••••'.e($doc['number_last4']).'</span>' : 'Not recorded' !!}</dd>
                                            <dt>Expiry date</dt><dd>{{ $doc['expiry'] ? $doc['expiry']->format('Y-m-d') : 'Not recorded' }}{{ $doc['expiry'] && $doc['expiry']->lt(now('UTC')->startOfDay()) ? ' · Expired' : '' }}</dd>
                                            <dt>Issuing country</dt><dd>{{ $doc['country'] ?? 'Not recorded' }}</dd>
                                            <dt>Uploaded</dt><dd>{{ $doc['uploaded']?->format('Y-m-d H:i') }} UTC</dd>
                                            @if($doc['note'])<dt>Issue noted</dt><dd>{{ $doc['note'] }}</dd>@endif
                                        </dl>
                                        @if($r['state'] === \App\Models\KycSubmission::UNDER_REVIEW)
                                            <label class="lm-kyc-ack">
                                                <input type="checkbox" data-lm-kyc-ack="{{ $doc['id'] }}" data-url="{{ route('admin.leadmax.leads.kyc.request.acknowledge', ['id' => $lead->id, 'submission' => $h->id, 'document' => $doc['id']]) }}" @checked($doc['acknowledged']) @disabled(! $r['canDecide'])>
                                                <span>Reviewed — I have checked this version of the document</span>
                                            </label>
                                            <small class="lm-kyc-hint">Marking a document as reviewed does not approve the request.</small>
                                        @elseif($doc['acknowledged'])
                                            <p class="lm-kyc-hint">Reviewed by {{ $doc['acknowledged_by'] }} · {{ $doc['acknowledged_at']?->format('Y-m-d H:i') }} UTC</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section role="tabpanel" id="lm-kyc-panel-details" aria-labelledby="lm-kyc-tab-details" data-lm-kyc-panel="details" @if($r['tab'] !== 'details') hidden @endif>
                    {{-- NC-24.5: said once, so each missing field can simply read "Not recorded". --}}
                    @if($r['legacy'])<p class="lm-kyc-notice lm-kyc-legacy-notice" role="note">Some submission details were not recorded in the previous system. Available historical information is shown below.</p>@endif
                    {{-- One section at a time, chosen here, so Details fits the modal without scrolling (user, 10 Oct 2026). --}}
                    <div class="lm-kyc-details-nav" role="group" aria-label="Request details">
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="request" aria-controls="lm-kyc-sec-request" aria-pressed="{{ $detailsSection === 'request' ? 'true' : 'false' }}">Request</button>
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="personal" aria-controls="lm-kyc-sec-personal" aria-pressed="{{ $detailsSection === 'personal' ? 'true' : 'false' }}">Personal</button>
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="address" aria-controls="lm-kyc-sec-address" aria-pressed="{{ $detailsSection === 'address' ? 'true' : 'false' }}">Address</button>
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="requirements" aria-controls="lm-kyc-sec-requirements" aria-pressed="{{ $detailsSection === 'requirements' ? 'true' : 'false' }}">Requirements</button>
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="documents" aria-controls="lm-kyc-sec-documents" aria-pressed="{{ $detailsSection === 'documents' ? 'true' : 'false' }}">Documents</button>
                        <button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="notes" aria-controls="lm-kyc-sec-notes" aria-pressed="{{ $detailsSection === 'notes' ? 'true' : 'false' }}">Notes</button>
                        @if($r['canRequestUpdate'])<button type="button" class="lm-kyc-details-chip" data-lm-kyc-section="update" aria-controls="lm-kyc-sec-update" aria-pressed="{{ $detailsSection === 'update' ? 'true' : 'false' }}">Request update</button>@endif
                    </div>
                    <div class="lm-kyc-details-grid">
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-request" data-lm-kyc-section-panel="request" aria-label="Request" @if($detailsSection !== 'request') hidden @endif><h3 class="sr-only">Request</h3>
                            <dl class="lm-kyc-request-summary">
                                <div><dt>Status</dt><dd><span class="lm-kyc-state lm-kyc-state-{{ $r['state'] }}">{{ $r['stateLabel'] }}</span></dd></div>
                                <div><dt>Client verification</dt><dd>{{ $clientStatus }}</dd></div>
                                <div><dt>Submitted</dt><dd>{{ $r['submittedAt'] ? $r['submittedAt']->format('Y-m-d H:i').' UTC' : 'Not recorded' }}</dd></div>
                                <div><dt>Purpose</dt><dd>{{ $r['purpose'] ?? 'Not recorded' }}</dd></div>
                                <div><dt>Reviewer</dt><dd>{{ $r['reviewer'] ? $r['reviewer'].($r['isFinal'] ? '' : ' · reviewing since '.$r['claimedAt']?->format('Y-m-d H:i')) : 'Not claimed' }}</dd></div>
                                <div><dt>Bundle</dt><dd>{{ $r['bundle']['label'] }}<small data-lm-kyc-bundle>{{ $r['bundle']['progress'] }}</small></dd></div>
                            </dl>
                        </section>
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-personal" data-lm-kyc-section-panel="personal" aria-label="Submitted personal details" @if($detailsSection !== 'personal') hidden @endif><h3 class="sr-only">Submitted personal details</h3>
                            @if($r['personal'])<dl class="lm-history-detail">@foreach($r['personal'] as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach</dl>@else<p class="lm-kyc-hint">Not recorded with this request.</p>@endif
                        </section>
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-address" data-lm-kyc-section-panel="address" aria-label="Submitted address" @if($detailsSection !== 'address') hidden @endif><h3 class="sr-only">Submitted address</h3>
                            @if($r['address'])<dl class="lm-history-detail">@foreach($r['address'] as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach</dl>@else<p class="lm-kyc-hint">Not recorded with this request.</p>@endif
                        </section>
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-requirements" data-lm-kyc-section-panel="requirements" aria-label="Requirements" @if($detailsSection !== 'requirements') hidden @endif><h3 class="sr-only">Requirements</h3>
                            <dl class="lm-history-detail">
                                <dt>Required documents</dt><dd>{{ implode(', ', $r['requiredLabels']) }}</dd>
                                <dt>Source</dt><dd>{{ $source }}{{ $r['submittedBy'] ? ' · '.$r['submittedBy'] : '' }}</dd>
                                <dt>Risk</dt><dd>{{ $r['risk'] ?? 'Not assessed' }}</dd>
                            </dl>
                        </section>
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-documents" data-lm-kyc-section-panel="documents" aria-label="Document details" @if($detailsSection !== 'documents') hidden @endif><h3 class="sr-only">Document details</h3>
                            @foreach($r['documents'] as $doc)
                                <dl class="lm-history-detail">
                                    <dt>{{ $doc['label'] }}</dt><dd>{{ $doc['number_last4'] ? '••••'.$doc['number_last4'] : 'Number not recorded' }} · {{ $doc['expiry'] ? 'expires '.$doc['expiry']->format('Y-m-d') : 'expiry not recorded' }} · {{ $doc['country'] ?? 'country not recorded' }}{{ $doc['metadata_source'] ? ' ('.str_replace('_', ' ', $doc['metadata_source']).')' : '' }}</dd>
                                </dl>
                                @unless($r['isFinal'])
                                    <details class="lm-kyc-meta-edit">
                                        <summary class="is-link">Record details for {{ $doc['label'] }}</summary>
                                        <form method="POST" action="{{ route('admin.leadmax.leads.kyc.request.metadata', ['id' => $lead->id, 'submission' => $h->id, 'document' => $doc['id']]) }}" data-lm-submit data-lm-success="{{ $selfUrl }}&kh_tab=details&kh_section=documents" class="lm-kyc-meta-form">
                                            @csrf
                                            <label>Document number <span class="lm-optional">Optional</span><input name="document_number" maxlength="50" value="{{ $doc['number_input'] }}" autocomplete="off"></label>
                                            <label>Expiry date <span class="lm-optional">Optional</span><input name="document_expiry" type="date" value="{{ $doc['expiry']?->format('Y-m-d') }}"></label>
                                            <label>Issuing country <span class="lm-optional">Optional</span><select name="issuing_country"><option value="">Not recorded</option>@foreach(array_keys(\App\Support\LeadMax\Geo::COUNTRIES) as $country)<option value="{{ $country }}" @selected($doc['country_input'] === $country)>{{ $country }}</option>@endforeach</select></label>
                                            <button type="submit" class="lm-button" data-lm-busy="Saving…">Save details</button>
                                            <p role="alert" class="lm-form-error" hidden></p>
                                        </form>
                                    </details>
                                @endunless
                            @endforeach
                        </section>
                        <section class="lm-kyc-detail-section" id="lm-kyc-sec-notes" data-lm-kyc-section-panel="notes" aria-label="Notes" @if($detailsSection !== 'notes') hidden @endif><h3 class="sr-only">Notes</h3>
                            <dl class="lm-history-detail">
                                <dt>Reason shown to the client</dt><dd>{{ $r['publicReason'] ?? 'None' }}</dd>
                            </dl>
                            <h4>Internal notes</h4>
                            @forelse($r['notes'] as $note)
                                <p class="lm-kyc-note"><strong>{{ $note['actor'] }}</strong>{{ $note['role'] ? ' ('.$note['role'].')' : '' }} · {{ $note['at']?->format('Y-m-d H:i') }} UTC<br>{{ $note['note'] }}</p>
                            @empty
                                <p class="lm-kyc-hint">No internal notes.</p>
                            @endforelse
                        </section>
                        @if($r['canRequestUpdate'])
                            <section class="lm-kyc-detail-section" id="lm-kyc-sec-update" data-lm-kyc-section-panel="update" aria-label="Request an update" @if($detailsSection !== 'update') hidden @endif><h3 class="sr-only">Request an update</h3>
                                <form method="POST" action="{{ route('admin.leadmax.leads.kyc.request.update', ['id' => $lead->id, 'submission' => $h->id]) }}" data-lm-submit data-lm-success="{{ $selfUrl }}&kh_tab=timeline" class="lm-kyc-update-form">
                                    @csrf
                                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                    <fieldset><legend>Documents to send again</legend>
                                        @foreach(\App\Models\UserId::REQUIRED_TYPES as $type)<label><input type="checkbox" name="types[]" value="{{ $type }}"> {{ (new \App\Models\UserId(['type' => $type]))->typeLabel() }}</label>@endforeach
                                    </fieldset>
                                    <label for="lm-kyc-update-reason">Reason shown to the client</label>
                                    <textarea id="lm-kyc-update-reason" name="reason" rows="2" minlength="3" maxlength="500" required></textarea>
                                    <p class="lm-kyc-hint">This records the request and notifies the client. It does not change this decision or the client's verification.</p>
                                    <button type="submit" class="lm-button" data-lm-busy="Sending…">Send update request</button>
                                    <p role="alert" class="lm-form-error" hidden></p>
                                </form>
                            </section>
                        @endif
                    </div>
                </section>

                <section role="tabpanel" id="lm-kyc-panel-timeline" aria-labelledby="lm-kyc-tab-timeline" data-lm-kyc-panel="timeline" @if($r['tab'] !== 'timeline') hidden @endif>
                    {{-- Newest first, paged to what fits, so the modal does not scroll (user, 10 Oct 2026). --}}
                    <ol class="lm-kyc-timeline" data-lm-kyc-timeline>
                        @forelse($r['timeline'] as $entry)
                            <li class="is-{{ $entry['type'] }}"><strong>{{ $entry['label'] }}</strong><span>{{ $entry['actor'] }}{{ $entry['role'] ? ' · '.$entry['role'] : '' }} · <time>{{ $entry['at']?->format('Y-m-d H:i') }} UTC</time></span>@if($entry['detail'])<small>{{ $entry['detail'] }}</small>@endif</li>
                        @empty
                            <li class="lm-kyc-hint">No events were recorded for this request; it predates the audit trail.</li>
                        @endforelse
                    </ol>
                    <div class="lm-kyc-timeline-pager" data-lm-kyc-timeline-pager hidden>
                        <button type="button" class="lm-button" data-lm-kyc-timeline-page="-1">Newer</button>
                        <span data-lm-kyc-timeline-range aria-live="polite"></span>
                        <button type="button" class="lm-button" data-lm-kyc-timeline-page="1">Older</button>
                    </div>
                </section>
            </div>

            <footer class="lm-dialog-footer lm-kyc-request-footer">
                @if(session('lm_kyc_notice'))<p class="lm-kyc-notice" role="status">{{ session('lm_kyc_notice') }}</p>@endif
                @if($r['isFinal'])
                    <p class="lm-kyc-decided" id="lm-kyc-decided">This request has already been decided: <strong>{{ $r['stateLabel'] }}</strong>{{ $r['reviewer'] ? ' by '.$r['reviewer'] : '' }}{{ $r['reviewedAt'] ? ' on '.$r['reviewedAt']->format('Y-m-d H:i').' UTC' : '' }}.{{ $r['publicReason'] ? ' Reason: '.$r['publicReason'] : '' }}</p>
                    <span class="lm-kyc-decision-buttons">
                        <a class="lm-button" href="{{ $closeUrl }}" data-lm-dialog-close>Close</a>
                        <button type="button" class="lm-button lm-kyc-deny" disabled aria-describedby="lm-kyc-decided">Deny</button>
                        <button type="button" class="lm-button lm-kyc-accept" disabled aria-describedby="lm-kyc-decided">Accept</button>
                    </span>
                @else
                    <div class="lm-kyc-footer-status">
                        @if($r['canStart'])
                            <form method="POST" action="{{ route('admin.leadmax.leads.kyc.review', ['id' => $lead->id, 'submission' => $h->id]) }}" data-lm-submit data-lm-success="{{ $selfUrl }}&kh_tab=documents">
                                @csrf<input type="hidden" name="target_state" value="under_review"><input type="hidden" name="version" value="{{ $h->version }}">
                                <button type="submit" class="lm-button primary" data-lm-busy="Starting…">Start Review</button>
                                <p role="alert" class="lm-form-error" hidden></p>
                            </form>
                            <span class="lm-kyc-hint" id="lm-kyc-accept-blocker">Start the review to accept or deny. Opening this request changes nothing.</span>
                        @elseif($r['canTakeOver'])
                            <span class="lm-kyc-hint" id="lm-kyc-accept-blocker">{{ $r['reviewer'] }} is reviewing this request.</span>
                            <form method="POST" action="{{ route('admin.leadmax.leads.kyc.request.take-over', ['id' => $lead->id, 'submission' => $h->id]) }}" data-lm-submit data-lm-success="{{ $selfUrl }}&kh_tab=documents">
                                @csrf<input type="hidden" name="version" value="{{ $h->version }}">
                                <button type="submit" class="lm-button" data-lm-busy="Taking over…">Take over review</button>
                                <p role="alert" class="lm-form-error" hidden></p>
                            </form>
                        @elseif($r['acceptBlocker'])
                            <span class="lm-kyc-hint" id="lm-kyc-accept-blocker" data-lm-kyc-blocker>{{ $r['acceptBlocker'] }}</span>
                        @else
                            <span class="lm-kyc-hint" id="lm-kyc-accept-blocker" data-lm-kyc-blocker></span>
                        @endif
                    </div>
                    <div class="lm-kyc-confirm" data-lm-kyc-confirm="rejected" hidden>
                        <label for="lm-kyc-deny-reason">Reason shown to the client (3–500 characters)</label>
                        <textarea id="lm-kyc-deny-reason" name="rejection_reason" rows="2" minlength="3" maxlength="500" data-lm-kyc-feedback="reason"></textarea>
                        <label for="lm-kyc-deny-note">Internal note (not shown to the client)</label>
                        <textarea id="lm-kyc-deny-note" name="notes" rows="2" maxlength="500" data-lm-kyc-feedback="note"></textarea>
                        <p>Deny {{ $r['reference'] }}? An approval the client already has stays in place.</p>
                        <span class="lm-kyc-confirm-actions"><button type="button" class="lm-button" data-lm-kyc-cancel>Keep reviewing</button><button type="button" class="lm-button lm-kyc-deny" data-lm-kyc-submit="rejected">Deny request</button></span>
                    </div>
                    <div class="lm-kyc-confirm" data-lm-kyc-confirm="approved" hidden>
                        <p>{{ $acceptCopy }}</p>
                        <span class="lm-kyc-confirm-actions"><button type="button" class="lm-button" data-lm-kyc-cancel>Keep reviewing</button><button type="button" class="lm-button lm-kyc-accept" data-lm-kyc-submit="approved">Accept request</button></span>
                    </div>
                    <p role="alert" class="lm-form-error" data-lm-kyc-error hidden></p>
                    <span class="lm-kyc-decision-buttons">
                        <a class="lm-button" href="{{ $closeUrl }}" data-lm-dialog-close>Close</a>
                        <button type="button" class="lm-button lm-kyc-deny" data-lm-kyc-ask="rejected" @disabled(! $r['canDecide']) aria-describedby="lm-kyc-accept-blocker">Deny</button>
                        <button type="button" class="lm-button lm-kyc-accept" data-lm-kyc-ask="approved" @disabled(! $r['canDecide'] || $r['acceptBlocker']) aria-describedby="lm-kyc-accept-blocker">Accept</button>
                    </span>
                @endif
            </footer>
        </div>
    @else
        <div class="lm-detail-dialog-body"><p class="lm-empty-state">This KYC request is not available for this lead.</p></div>
        <footer class="lm-dialog-footer"><a class="lm-button primary" href="{{ $closeUrl }}">Close</a></footer>
    @endif
@elseif($dialog === 'kyc-notes')
    <div class="lm-detail-dialog-body">
        @if($kycNotes)
            <dl class="lm-history-detail"><dt>Reason shown to the client</dt><dd>{{ $kycNotes['publicReason'] ?: 'Not recorded' }}</dd></dl>
            <h3>Internal reviewer notes</h3>
            @forelse($kycNotes['notes'] as $note)
                <p><strong>{{ $note['actor'] }}</strong>{{ $note['role'] ? ' ('.$note['role'].')' : '' }} · {{ $note['at']?->format('Y-m-d H:i') }}<br>{{ $note['note'] }}</p>
            @empty
                <p class="lm-empty-state">No internal notes.</p>
            @endforelse
        @else
            <p class="lm-empty-state">This submission is not available.</p>
        @endif
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button primary" href="{{ $closeUrl }}">Close</a></footer>
@elseif($dialog === 'service-history')
    <div class="lm-detail-dialog-body"><div class="lm-detail-table-wrap"><table class="lm-detail-table"><thead><tr><th>Opened</th><th>Subject</th><th>Category</th><th>Priority</th><th>Assigned</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($tickets as $ticket)<tr><td>{{ $ticket->created_at?->format('Y-m-d H:i') }}</td><td>{{ $ticket->subject }}</td><td>{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</td><td>{{ ucfirst($ticket->priority ?? 'medium') }}{{ $ticket->escalated_at ? ' · escalated' : '' }}</td><td>{{ $ticket->assignee?->name ?: '—' }}</td><td><span class="lm-detail-pill">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td><td><a href="{{ route('admin.support-tickets.show', $ticket->id) }}">Open →</a></td></tr>
    @empty<tr><td colspan="7" class="lm-empty-state">No service requests yet.</td></tr>@endforelse
    </tbody></table></div></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Close</a><a class="lm-button primary" href="{{ $dialogUrl('service') }}">New request</a></footer>
@elseif($dialog === 'cancel-service')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.service.cancel', $lead->id) }}" method="POST" data-lm-success="{{ $dialogUrl('service-history') }}">@csrf
    <div class="lm-detail-dialog-body">@forelse($openTickets as $ticket)<label class="lm-detail-radio"><input type="radio" name="ticket_id" value="{{ $ticket->id }}" required @checked($loop->first)><span><strong>{{ $ticket->subject }}</strong><small>{{ ucfirst(str_replace('_', ' ', $ticket->category)) }} · opened {{ $ticket->created_at?->format('Y-m-d') }}</small></span></label>@empty<p class="lm-empty-state">There are no open service requests.</p>@endforelse<p role="alert" class="lm-form-error" hidden></p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Keep open</a>@if($openTickets->isNotEmpty())<button class="lm-button danger">Cancel request</button>@endif</footer></form>
@elseif(in_array($dialog, ['invoice', 'credit-note'], true))
    <form data-lm-submit action="{{ route('admin.leadmax.leads.invoices.store', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('finances') }}">@csrf<input type="hidden" name="type" value="{{ $dialog === 'invoice' ? 'invoice' : 'credit_note' }}">
    <div class="lm-detail-dialog-body">
        @if($dialog === 'credit-note')<label>Against invoice<select name="invoice_id"><option value="">No invoice (standalone credit)</option>@foreach($openInvoices as $open)<option value="{{ $open->id }}">{{ $open->number }} · {{ $open->currency }} {{ number_format($open->balanceDue(), 2) }} open</option>@endforeach</select></label>@endif
        <div class="lm-invoice-lines"><div class="lm-invoice-line lm-invoice-line-head" aria-hidden="true"><span>Description</span><span>Qty</span><span>Unit price</span></div><div class="lm-invoice-line" data-lm-invoice-line><input name="items[0][description]" required maxlength="255" placeholder="Description" aria-label="Description"><input name="items[0][quantity]" type="number" required min="0.01" step="0.01" value="1" aria-label="Quantity"><input name="items[0][unit_price]" type="number" required min="0.01" step="0.01" placeholder="0.00" aria-label="Unit price"></div></div>
        <button type="button" class="lm-button" data-lm-add-invoice-line>+ Add line</button>
        <div class="lm-reminder-row"><label>Currency<select name="currency"><option>USD</option><option>EUR</option><option>GBP</option></select></label><label>Issue date<input name="issue_date" type="date" required value="{{ now()->toDateString() }}"></label>@if($dialog === 'invoice')<label>Due date<input name="due_date" type="date" value="{{ now()->addDays(14)->toDateString() }}"></label>@endif</div>
        <label>Notes<textarea name="notes" maxlength="2000" rows="3" placeholder="{{ $dialog === 'invoice' ? 'Payment instructions or terms' : 'Reason for the credit' }}"></textarea></label>
        @if($hasEmail && ! $communicationsBlocked)<label class="lm-detail-check"><input type="checkbox" name="send" value="1"> Email it to {{ $lead->email }}</label>@endif
        <p role="alert" class="lm-form-error" hidden></p>
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">{{ $dialog === 'invoice' ? 'Create invoice' : 'Create credit note' }}</button></footer></form>
@elseif(in_array($dialog, ['escalate', 'assign-support', 'ticket-priority'], true))
    <form data-lm-submit action="{{ route('admin.leadmax.leads.service.update', $lead->id) }}" method="POST" data-lm-success="{{ $dialogUrl('service-history') }}">@csrf<input type="hidden" name="action" value="{{ ['escalate' => 'escalate', 'assign-support' => 'assign', 'ticket-priority' => 'priority'][$dialog] }}">
    <div class="lm-detail-dialog-body">
        @if($openTickets->isEmpty())<p class="lm-empty-state">There are no open service requests.</p>@else
        @foreach($openTickets as $ticket)<label class="lm-detail-radio"><input type="radio" name="ticket_id" value="{{ $ticket->id }}" required @checked($loop->first)><span><strong>{{ $ticket->subject }}</strong><small>{{ ucfirst($ticket->priority ?? 'medium') }} priority · {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}{{ $ticket->assignee ? ' · '.$ticket->assignee->name : '' }}</small></span></label>@endforeach
        @if($dialog === 'assign-support')<label>Support agent<select name="assigned_to" required>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></label>@endif
        @if($dialog === 'ticket-priority')<label>Priority<select name="priority" required>@foreach(\App\Models\SupportTicket::PRIORITIES as $priority)<option value="{{ $priority }}" @selected($priority === 'high')>{{ ucfirst($priority) }}</option>@endforeach</select></label>@endif
        <label>{{ $dialog === 'escalate' ? 'Reason for escalation' : 'Note (optional)' }}<textarea name="reason" maxlength="2000" rows="3" @if($dialog === 'escalate') required @endif></textarea></label>
        @endif
        <p role="alert" class="lm-form-error" hidden></p>
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a>@if($openTickets->isNotEmpty())<button class="lm-button {{ $dialog === 'escalate' ? 'danger' : 'primary' }}">{{ ['escalate' => 'Escalate', 'assign-support' => 'Assign', 'ticket-priority' => 'Update priority'][$dialog] }}</button>@endif</footer></form>
@elseif($dialog === 'signal')
    <form data-lm-submit action="{{ route('admin.leadmax.leads.signals.store', $lead->id) }}" method="POST" data-lm-success="{{ $tabUrl('signals') }}">@csrf
    <div class="lm-detail-dialog-body">
        <div class="lm-reminder-row"><label>Instrument<input name="instrument" required maxlength="30" placeholder="e.g. EUR/USD" list="lm-signal-instruments"></label><label>Direction<select name="direction" required><option value="buy">Buy</option><option value="sell">Sell</option></select></label></div>
        <datalist id="lm-signal-instruments">@foreach(['EUR/USD', 'GBP/USD', 'USD/JPY', 'GBP/JPY', 'EUR/GBP', 'XAU/USD', 'BTC/USD', 'ETH/USD', 'NAS100', 'US30'] as $symbol)<option value="{{ $symbol }}">@endforeach</datalist>
        <div class="lm-reminder-row"><label>Entry Price<input name="entry_price" type="number" step="any" min="0" required></label><label>Take Profit<input name="take_profit" type="number" step="any" min="0"></label><label>Stop Loss<input name="stop_loss" type="number" step="any" min="0"></label></div>
        <label>Note<textarea name="note" maxlength="1000" rows="3" placeholder="Optional context for the client"></textarea></label>
        @unless($communicationsBlocked)<label class="lm-detail-check"><input type="checkbox" name="notify" value="1" checked> Notify the client in the client area</label>@endunless
        <p role="alert" class="lm-form-error" hidden></p>
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Send Signal</button></footer></form>
@elseif($dialog === 'signal-close')
    @if($signalToClose)
    <form data-lm-submit action="{{ route('admin.leadmax.leads.signals.update', [$lead->id, $signalToClose->id]) }}" method="POST" data-lm-success="{{ $tabUrl('signals') }}">@csrf
    <div class="lm-detail-dialog-body">
        <label>Outcome<select name="status"><option value="closed">Closed</option><option value="expired">Expired</option></select></label>
        <label>Result<input name="result" maxlength="40" placeholder="e.g. +48 pips, -$120, 0 pips"></label>
        <p role="alert" class="lm-form-error" hidden></p>
    </div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Cancel</a><button class="lm-button primary">Save</button></footer></form>
    @else
    <div class="lm-detail-dialog-body"><p class="lm-empty-state">This signal is no longer available.</p></div>
    <footer class="lm-dialog-footer"><a class="lm-button" href="{{ $closeUrl }}">Close</a></footer>
    @endif
@endif
</section></div>
