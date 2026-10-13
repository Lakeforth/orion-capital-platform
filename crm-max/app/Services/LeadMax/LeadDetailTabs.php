<?php

namespace App\Services\LeadMax;

use App\Http\Controllers\Admin\LeadMaxFinanceController;
use App\Models\ActivityLog;
use App\Models\LeadSignal;
use App\Models\LoginHistory;
use App\Models\Message;
use App\Models\User;
use App\Models\UserId;
use App\Services\IpCountry;
use App\Support\Kyc;
use App\Support\LeadMax\Geo;
use App\Support\LeadMax\Phone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the Lead Details bottom tabs (Figma "Max Leads user details" *-tab-page frames).
 * Only real data is shown: values the platform does not record are left null and rendered as "—".
 */
class LeadDetailTabs
{
    public const PAGE = 'tp';

    public const ACCOUNT_CHANGE_TYPES = ['lead_updated', 'password_change', 'status_changed', 'owner_assigned', 'block', 'unblock', 'block_communications', 'unblock_communications'];

    public function data(User $lead, string $tab, Request $request): array
    {
        return match ($tab) {
            'finances' => $this->finances($lead, $request),
            'trading' => $this->trading($lead),
            'kyc' => $this->kyc($lead, $request),
            'history' => $this->history($lead, $request),
            'calls' => $this->calls($lead, $request),
            'chat' => $this->chat($lead, $request),
            'signals' => $this->signals($lead, $request),
            'sessions' => $this->sessions($lead, $request),
            default => [],
        };
    }

    private function finances(User $lead, Request $request): array
    {
        $approved = fn (array $types) => (float) $lead->transactions()->whereIn('type', $types)->where('status', 'approved')->sum('amount');

        return [
            'balance' => (float) $lead->balance,
            'deposits' => $approved(LeadMaxFinanceController::CLIENT_DEPOSIT_TYPES),
            'withdrawals' => $approved(LeadMaxFinanceController::CLIENT_WITHDRAWAL_TYPES),
            'pending' => $lead->transactions()->where('status', 'pending')->count(),
            'rows' => $lead->transactions()->latest()->paginate(10, ['*'], self::PAGE)->withQueryString(),
        ];
    }

    private function trading(User $lead): array
    {
        $accounts = $lead->tradingAccounts()->latest()->get();
        $trades = \App\Models\Trade::query()->where('user_id', $lead->id)->with(['asset', 'tradingAccount'])
            ->orderBy('status')->latest()->limit(50)->get();

        return [
            'accounts' => $accounts,
            'trades' => $trades,
            'open_trades' => $trades->where('status', 0)->count(),
            'trade_count' => \App\Models\Trade::query()->where('user_id', $lead->id)->count(),
            'spread' => $lead->spread_multiplier,
            'active' => $accounts->where('is_active', true)->count(),
            'equity' => $accounts->sum(fn ($account) => $account->equity()),
            'pnl' => (float) $accounts->sum('profit'),
        ];
    }

    /** Activity Log columns that sort (?hs=key), mapped to the row field they sort on. */
    public const HISTORY_SORTS = [
        'log' => 'log_id', 'owner' => 'actor', 'at' => 'at', 'event' => 'event', 'role' => 'role', 'country' => 'country', 'result' => 'status',
    ];

    private function kyc(User $lead, Request $request): array
    {
        $documents = $lead->kyc()->with('reviewer')->orderByDesc('attempt')->latest()->get();
        $attempts = Kyc::attempts($lead);
        $latest = $attempts->first();
        // Every attempt gets its header (made with the backfill's mapping if it predates
        // headers), so history can show each attempt's state and notes. The latest one is
        // what the Update KYC Status panel acts on.
        $lifecycle = app(\App\Services\KycLifecycle::class);
        $headers = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->get()->keyBy('attempt');
        foreach ($attempts as $attempt) {
            if (! $headers->has($attempt['attempt'])) {
                $headers->put($attempt['attempt'], $lifecycle->header($lead, $attempt['attempt']));
            }
        }
        $header = $lifecycle->current($lead);

        // Every file the reviewer can open. The URL is this application's own
        // streaming route, never the object's S3 URL -- the bucket denies
        // anonymous reads, which is why these images were all broken before.
        $filesOf = fn ($docs) => collect($docs)->flatMap(fn (UserId $document) => collect(['id_front' => 'front', 'id_back' => 'back'])
            ->filter(fn ($side, $field) => filled($document->{$field}))
            ->map(fn ($side, $field) => [
                'id' => $document->id,
                'attempt' => (int) $document->attempt,
                'side' => $side,
                'name' => $document->original_name
                    ?: basename((string) (parse_url((string) $document->{$field}, PHP_URL_PATH) ?: $document->{$field})),
                'url' => route('admin.leadmax.leads.kyc.file', ['id' => $document->user_id, 'document' => $document->id, 'side' => $side]),
                'type' => $document->typeLabel(),
                'status' => strtolower((string) ($document->status ?: UserId::PENDING)),
                'note' => $document->review_note,
                'reviewer' => $document->reviewer?->name,
                'reviewed' => $document->reviewed_at,
                'uploaded' => $document->created_at,
            ])
            ->values())->values();

        $byAttempt = $documents->groupBy('attempt');
        // The current attempt is the header's: after New KYC it is a draft with no files yet.
        // Earlier attempts are the request history (NC-23), one row per request, not per file.
        $currentAttempt = $header?->attempt ?? ($latest['attempt'] ?? 0);

        return [
            'status' => strtolower((string) ($lead->verification_status ?: 'not submitted')),
            'documents' => $documents,
            'header' => $header,
            'headers' => $headers,
            // The current request's files, for the Documents view, and its bundle count (NC-24.3).
            'files' => $filesOf($byAttempt->get($currentAttempt, collect())),
            'bundle' => $header ? self::kycBundle($header, $byAttempt->get($currentAttempt, collect())) : null,
            'attempts' => $attempts,
            // NC-23: the request waiting for a decision, the request history with its cards, and
            // the audit trail. (Levels were removed on 10 Oct 2026: one KYC throughout.)
            'view' => array_key_exists((string) $request->input('kyc_view'), self::KYC_VIEWS) ? $request->input('kyc_view') : 'history',
            // The latest KYC event for the Last Activity card.
            'lastEvent' => \App\Models\KycEvent::withoutGlobalScopes()->where('user_id', $lead->id)->whereNotIn('type', self::HIDDEN_KYC_EVENTS)->latest('id')->first(),
            'active' => $headers->first(fn ($h) => in_array($h->state, [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW], true)),
            'latestDecided' => $headers->filter(fn ($h) => in_array($h->state, [\App\Models\KycSubmission::APPROVED, \App\Models\KycSubmission::REJECTED], true))->sortByDesc('attempt')->first(),
            'requests' => $list = $this->kycRequestList($lead, $request),
            'requestPage' => $this->kycRequestPage($list['rows'], $request),
            'requestPerPage' => $this->kycRequestPerPage($request),
        ];
    }

    public function kycRequestPerPage(Request $request): int
    {
        return in_array((int) $request->input('kh_per'), self::KYC_REQUEST_SIZES, true) ? (int) $request->input('kh_per') : 50;
    }

    private function kycRequestPage(Collection $rows, Request $request): LengthAwarePaginator
    {
        $perPage = $this->kycRequestPerPage($request);
        $page = max(1, (int) $request->input('kh_page', 1));

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
            'path' => $request->url(), 'pageName' => 'kh_page',
            // Paging keeps the filters and the view, never a dialog that happened to be open.
            'query' => collect($request->query())->except(['dialog', 'request', 'kh_tab', 'doc'])->all(),
        ]);
    }

    /**
     * Everything the request-review modal shows (NC-23.4): the request's header facts, every
     * document in its bundle with its sides and availability, the snapshot and metadata,
     * the timeline, and what the viewing agent may do. Read-only: opening it changes nothing.
     * Only a request of this lead opens here.
     */
    public function kycRequest(User $lead, string $requestId, Request $request): ?array
    {
        $header = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->with(['reviewer', 'submitter'])->find($requestId);
        if (! $header) {
            return null;
        }

        $lifecycle = app(\App\Services\KycLifecycle::class);
        $documents = $lifecycle->documentsOf($header)->load(['reviewer']);
        $required = UserId::REQUIRED_TYPES;
        $acknowledgers = User::withoutGlobalScopes()->whereIn('id', $documents->pluck('acknowledged_by')->filter()->unique())->get()->keyBy('id');
        $legacy = $header->personal ?? [];

        $items = $documents->map(function (UserId $document) use ($lead, $required, $acknowledgers, $legacy) {
            $sides = collect(['front' => $document->id_front, 'back' => $document->id_back])->filter()
                ->map(function ($stored, $side) use ($lead, $document) {
                    $key = (string) Kyc::storageKey($stored);
                    $extension = strtolower(pathinfo((string) parse_url($key, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $url = route('admin.leadmax.leads.kyc.file', ['id' => $lead->id, 'document' => $document->id, 'side' => $side]);

                    return [
                        'side' => $side,
                        'available' => $key !== '' && \Illuminate\Support\Facades\Storage::disk('s3')->exists($key),
                        'pdf' => $side === 'front' && $document->mime ? $document->mime === 'application/pdf' : $extension === 'pdf',
                        'preview' => $url,
                        'download' => $url.'?download=1',
                    ];
                })->values();
            $isIdentity = $document->type === 'id';
            $countryCode = $document->issuing_country ?? ($isIdentity && filled($legacy['issuing_country'] ?? null) ? Geo::find($legacy['issuing_country'])['code'] : null);

            return [
                'id' => $document->id,
                'label' => $document->document_label ?: $document->typeLabel(),
                'type' => $document->typeLabel(),
                'category' => match ($document->type) {
                    'id' => 'Identity', 'proof_of_address' => 'Address', default => 'Supporting'
                },
                'required' => in_array($document->type, $required, true),
                'sides' => $sides,
                'version' => $document->sha256 ? substr($document->sha256, 0, 12) : null,
                'uploaded' => $document->created_at,
                'status' => strtolower((string) ($document->status ?: UserId::PENDING)),
                'note' => $document->review_note,
                'acknowledged' => (bool) $document->acknowledged_at,
                'acknowledged_by' => $document->acknowledged_by ? ($acknowledgers->get($document->acknowledged_by)?->name ?? 'Removed user') : null,
                'acknowledged_at' => $document->acknowledged_at,
                // Metadata belongs to the document; a legacy snapshot's values fill in for the ID only.
                'number_last4' => $document->document_number_last4 ?? ($isIdentity && filled($legacy['document_number'] ?? null) ? mb_substr(preg_replace('/\s+/', '', (string) $legacy['document_number']), -4) : null),
                'expiry' => $document->document_expiry ?? ($isIdentity ? $this->kycDate($legacy['document_expiry'] ?? null) : null),
                'country_code' => $countryCode ? strtolower($countryCode) : null,
                'country' => $countryCode ? $this->countryName($countryCode) : null,
                'metadata_source' => $document->metadata_source ?? ($isIdentity && (filled($legacy['document_number'] ?? null) || filled($legacy['document_expiry'] ?? null)) ? 'legacy_snapshot' : null),
                'country_input' => $document->issuing_country ? (collect(Geo::COUNTRIES)->search(fn ($row) => $row[0] === strtolower($document->issuing_country)) ?: null) : null,
                'number_input' => $document->document_number,
            ];
        })->sortBy(fn ($item) => [$item['category'] === 'Identity' ? 0 : ($item['category'] === 'Address' ? 1 : 2)])->values();

        $tab = in_array($request->input('kh_tab'), ['details', 'documents', 'timeline'], true) ? $request->input('kh_tab') : 'documents';
        $selected = $items->firstWhere('id', $request->input('doc')) ?? $items->first();
        $me = auth()->id();
        $final = $header->isFinal();
        $notes = $this->kycNotes($lead, $header->id);

        return [
            'header' => $header,
            'reference' => $header->reference ?? 'Attempt '.$header->attempt,
            'state' => $header->state,
            'stateLabel' => self::kycStateLabel($header->state),
            'legacy' => self::kycIsLegacy($header),
            'purpose' => \App\Models\KycSubmission::PURPOSES[$header->purpose] ?? null,
            'submittedAt' => $header->submitted_at,
            'submittedBy' => $header->submitter?->name,
            'source' => $header->source,
            'reviewer' => $header->reviewer_id ? ($header->reviewer?->name ?? 'Removed user') : null,
            'claimedAt' => $header->claimed_at,
            'reviewedAt' => $header->reviewed_at,
            'required' => $required,
            'requiredLabels' => collect($required)->map(fn ($type) => (new UserId(['type' => $type]))->typeLabel())->all(),
            // The one bundle count every KYC view shows (NC-24.3).
            'bundle' => self::kycBundle($header, $documents),
            'documents' => $items,
            'selected' => $selected,
            // The side the file reader was on, so closing it returns to the same side (NC-24.4).
            'side' => in_array($request->input('side'), ['front', 'back'], true) ? $request->input('side') : null,
            'tab' => $tab,
            'personal' => array_filter([
                'Full name' => trim(($legacy['first_name'] ?? '').' '.($legacy['last_name'] ?? '')) ?: null,
                'Date of birth' => $legacy['birthdate'] ?? null,
                'Nationality' => $legacy['nationality'] ?? null,
                'Phone' => trim(($legacy['dial_code'] ?? '').' '.($legacy['phone'] ?? '')) ?: null,
            ]),
            'address' => array_filter([
                'Street' => $header->address['street'] ?? null,
                'City' => $header->address['city'] ?? null,
                'Postcode' => $header->address['postcode'] ?? null,
                'Country' => $header->address['country'] ?? null,
                'Document type' => \App\Services\KycLifecycle::addressLabel($header->address ?? []),
            ]),
            'risk' => $header->risk_score !== null ? number_format((float) $header->risk_score, 0).($header->risk_band ? ' · '.ucfirst($header->risk_band) : ' · Band not assessed') : null,
            'publicReason' => $header->public_note,
            'notes' => $notes['notes'] ?? collect(),
            // Newest first: the modal shows as many events as fit and pages back to older ones.
            'timeline' => \App\Models\KycEvent::withoutGlobalScopes()->where('submission_id', $header->id)->whereNotIn('type', self::HIDDEN_KYC_EVENTS)->with('actor')->orderByDesc('id')->get()
                ->map(fn (\App\Models\KycEvent $event) => [
                    'label' => self::KYC_EVENT_LABELS[$event->type] ?? ucfirst(str_replace('_', ' ', $event->type)),
                    'type' => $event->type,
                    'actor' => $event->actor?->name ?? ($event->actor_id ? 'Removed user' : 'System'),
                    'role' => $event->actor_role,
                    'at' => $event->created_at,
                    'detail' => $this->kycEventDetail($event),
                ]),
            'isFinal' => $final,
            'canStart' => $header->state === \App\Models\KycSubmission::PENDING_APPROVAL,
            'canTakeOver' => $header->state === \App\Models\KycSubmission::UNDER_REVIEW && $header->reviewer_id && $header->reviewer_id !== $me,
            'canDecide' => $header->state === \App\Models\KycSubmission::UNDER_REVIEW && (! $header->reviewer_id || $header->reviewer_id === $me),
            'acceptBlocker' => $header->state === \App\Models\KycSubmission::UNDER_REVIEW ? $lifecycle->acceptBlocker($header) : null,
            'canRequestUpdate' => in_array($header->state, [\App\Models\KycSubmission::APPROVED, \App\Models\KycSubmission::REJECTED], true)
                && ! \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->whereIn('state', [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW])->exists(),
        ];
    }

    private function history(User $lead, Request $request): array
    {
        $activity = ActivityLog::query()->where('user_id', $lead->id)->latest()->get();
        $logins = LoginHistory::query()->where('user_id', $lead->id)->latest()->get();
        // Each row also says who acted, in which role, from where and on what -- the compact
        // table shows these as short labels; the full detail opens in the history-event dialog.
        $events = $activity->map(fn ($item) => [
            'at' => $item->created_at, 'event' => $this->eventLabel($item->type), 'description' => $item->title.(($item->info['description'] ?? null) ? ' — '.$item->info['description'] : ''),
            'ip' => $item->info['ip'] ?? null, 'status' => $this->eventStatus($item), 'agent' => $this->shortAgent($item->info['user_agent'] ?? null),
            'id' => $item->id, 'kind' => 'activity', 'actor' => $item->info['actor'] ?? 'System', 'role' => $item->info['actor_role'] ?? 'Not recorded',
            ...$this->ipCountry($item->info['country'] ?? null, $item->info['country_code'] ?? null, $item->info['ip'] ?? null, $item->info['country_source'] ?? null, $item->info['country_at'] ?? null),
        ])->concat($logins->map(fn ($login) => [
            'at' => $login->created_at, 'event' => 'Login', 'description' => $login->status === 'failed' ? 'Failed login attempt' : 'Successful login',
            'ip' => $login->ip_address, 'status' => $login->status === 'failed' ? 'Failed' : 'Success', 'agent' => $login->browser,
            'id' => $login->id, 'kind' => 'login', 'actor' => $lead->name, 'role' => 'Client',
            ...$this->ipCountry($login->location, null, $login->ip_address, $login->location ? IpCountry::SOURCE : null, $login->created_at?->toIso8601String()),
        ]))->sortByDesc('at')->values();
        // A log ID per lead, numbered oldest first (ACL-0001), so sorting and paging never renumber a row.
        $logIds = $events->sortBy(fn ($row) => [$row['at']?->format('Y-m-d H:i:s.u'), $row['kind'], (string) $row['id']])->values()
            ->mapWithKeys(fn ($row, $i) => [$row['kind'].':'.$row['id'] => sprintf('ACL-%04d', $i + 1)]);
        $events = $events->map(fn ($row) => $row + ['log_id' => $logIds[$row['kind'].':'.$row['id']]]);
        $lastActivity = $events->first()['at'] ?? null;

        // Every column sorts, like the other CRM tables (?hs=column&hd=asc|desc); newest first by default.
        $sort = array_key_exists((string) $request->input('hs'), self::HISTORY_SORTS) ? $request->input('hs') : 'at';
        $direction = $request->input('hd') === 'asc' ? 'asc' : 'desc';
        $field = self::HISTORY_SORTS[$sort];
        $sorted = $events->sortBy(
            fn ($row) => $field === 'at' ? (string) $row['at']?->format('Y-m-d H:i:s.u') : mb_strtolower((string) $row[$field]),
            SORT_NATURAL,
            $direction === 'desc'
        )->values();

        return [
            'total' => $events->count(),
            'loginCount' => $logins->count(),
            'changeCount' => $activity->whereIn('type', self::ACCOUNT_CHANGE_TYPES)->count(),
            'lastActivity' => $lastActivity,
            'rows' => $this->paginate($sorted, 5, $request),
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function calls(User $lead, Request $request): array
    {
        $calls = ActivityLog::query()->where('user_id', $lead->id)->where('type', 'call_history_created')->latest()->get()
            ->sortByDesc(fn ($call) => $call->info['called_at'] ?? $call->created_at)->values();
        $seconds = $calls->map(fn ($call) => $this->seconds($call->info['duration'] ?? null))->filter(fn ($s) => $s !== null);
        $answered = $calls->filter(fn ($call) => ($call->info['call_status'] ?? null) === 'answered')->count();

        return [
            'calls' => $calls,
            'total' => $calls->count(),
            'inbound' => $calls->filter(fn ($call) => ($call->info['call_type'] ?? null) === 'inbound')->count(),
            'outbound' => $calls->filter(fn ($call) => ($call->info['call_type'] ?? null) === 'outbound')->count(),
            'avgDuration' => $seconds->isNotEmpty() ? $this->duration((int) round($seconds->avg())) : null,
            'answeredRate' => $calls->isNotEmpty() ? round($answered / $calls->count() * 100).'%' : null,
            'missed' => $calls->filter(fn ($call) => ($call->info['call_type'] ?? null) === 'missed' || ($call->info['call_status'] ?? null) === 'unanswered')->count(),
            'voicemails' => $calls->filter(fn ($call) => ($call->info['call_status'] ?? null) === 'voicemail')->count(),
            'callbacks' => ActivityLog::query()->where('user_id', $lead->id)->where('type', 'reminder_created')->count(),
            'last' => $calls->first(),
            'rows' => $this->paginate($calls, 5, $request),
        ];
    }

    private function chat(User $lead, Request $request): array
    {
        $messages = Message::query()->without(['attachments'])->with('sender')->whereNull('conversation_type')->where('customer_id', $lead->id)->oldest()->get();
        // A chat is one day of conversation with the client.
        $sessions = $messages->groupBy(fn ($m) => $m->created_at->toDateString())->map(function (Collection $day) {
            $staff = $day->where('is_admin', true);
            $responses = $day->where('is_admin', false)->map(function ($message) use ($day) {
                $reply = $day->first(fn ($m) => $m->is_admin && $m->created_at->greaterThan($message->created_at));

                return $reply ? $message->created_at->diffInSeconds($reply->created_at, true) : null;
            })->filter(fn ($s) => $s !== null);
            $last = $day->last();
            $open = ! $last->is_admin;

            return [
                'start' => $day->first()->created_at, 'end' => $last->created_at,
                'duration' => $this->duration((int) $day->first()->created_at->diffInSeconds($last->created_at, true)),
                'agent' => $staff->last()?->sender?->name,
                'status' => $staff->isEmpty() ? ($last->created_at->isToday() ? 'Waiting' : 'Missed') : ($open && $last->created_at->isToday() ? 'Active' : 'Resolved'),
                'topic' => \Illuminate\Support\Str::limit($day->firstWhere('is_admin', false)?->message ?? $day->first()->message, 60),
                'firstResponse' => $responses->first(), 'responses' => $responses, 'messages' => $day->count(),
            ];
        })->sortByDesc('start')->values();
        $responses = $sessions->flatMap(fn ($s) => $s['responses']);
        $firsts = $sessions->pluck('firstResponse')->filter(fn ($s) => $s !== null);

        return [
            'total' => $sessions->count(),
            'live' => $sessions->whereIn('status', ['Active', 'Waiting'])->count(),
            'resolved' => $sessions->where('status', 'Resolved')->count(),
            'avgResponse' => $responses->isNotEmpty() ? $this->duration((int) round($responses->avg())) : null,
            'resolutionRate' => $sessions->isNotEmpty() ? round($sessions->where('status', 'Resolved')->count() / $sessions->count() * 100).'%' : null,
            'avgFirstResponse' => $firsts->isNotEmpty() ? $this->duration((int) round($firsts->avg())) : null,
            'avgHandle' => $sessions->isNotEmpty() ? $this->duration((int) round($sessions->avg(fn ($s) => $s['start']->diffInSeconds($s['end'], true)))) : null,
            'missed' => $sessions->where('status', 'Missed')->count(),
            'last' => $sessions->first(),
            'rows' => $this->paginate($sessions, 5, $request),
        ];
    }

    private function signals(User $lead, Request $request): array
    {
        return ['rows' => LeadSignal::query()->where('user_id', $lead->id)->latest()->paginate(8, ['*'], self::PAGE)->withQueryString()];
    }

    private function sessions(User $lead, Request $request): array
    {
        $tokens = DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $lead->id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderByDesc('last_active_at')->orderByDesc('created_at')->get();
        $logins = LoginHistory::query()->where('user_id', $lead->id)->latest()->get();
        $recent = $logins->where('created_at', '>=', now()->subDays(30));
        $failedIps = $recent->where('status', 'failed')->pluck('ip_address')->filter()->unique();
        $passwordChange = ActivityLog::query()->where('user_id', $lead->id)->where('type', 'password_change')->latest()->value('created_at');

        return [
            'tokens' => $tokens->map(fn ($token) => (array) $token + ['active' => $token->last_active_at && Carbon::parse($token->last_active_at)->isAfter(now()->subMinutes(30))]),
            'total' => $logins->where('status', '!=', 'failed')->count(),
            'activeNow' => $tokens->filter(fn ($token) => $token->last_active_at && Carbon::parse($token->last_active_at)->isAfter(now()->subMinutes(30)))->count(),
            'lastLogin' => $logins->firstWhere('status', '!=', 'failed')?->created_at,
            'failedAttempts' => $recent->where('status', 'failed')->count(),
            'suspiciousIp' => $failedIps->count() >= 3,
            'multipleDevices' => $tokens->pluck('device_type')->filter()->unique()->count() > 1,
            'lastPasswordChange' => $passwordChange,
            'geoAnomaly' => $recent->pluck('location')->filter()->map(fn ($l) => trim((string) last(explode(',', $l))))->unique()->count() > 1,
            'rows' => $this->paginate($logins, 5, $request),
        ];
    }

    private function paginate(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input(self::PAGE, 1));

        return (new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, ['path' => $request->url(), 'pageName' => self::PAGE]))->withQueryString();
    }

    public function duration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? $hours.'h '.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).'m' : $minutes.'m '.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT).'s';
    }

    /** Quote precision per instrument, as shown in the Figma signals table (FX 4dp, JPY 3dp, metals/ETH 2dp, BTC and indices 0dp). */
    public function precision(string $instrument): int
    {
        $symbol = strtoupper($instrument);

        return match (true) {
            str_contains($symbol, 'JPY') => 3,
            str_starts_with($symbol, 'XAU'), str_starts_with($symbol, 'XAG'), str_starts_with($symbol, 'ETH') => 2,
            str_starts_with($symbol, 'BTC'), (bool) preg_match('/\d/', $symbol) => 0,
            (bool) preg_match('/^[A-Z]{3}\/?[A-Z]{3}$/', $symbol) => 4,
            default => 2,
        };
    }

    /** "00:06:12" -> "6m 12s" */
    public function clock(?string $duration): ?string
    {
        return $this->duration($this->seconds($duration));
    }

    private function seconds(?string $duration): ?int
    {
        if (! $duration || ! preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $duration, $m)) {
            return null;
        }

        return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3];
    }

    private function documentType(?string $type): string
    {
        return $type ? ucwords(str_replace(['_', '-'], ' ', $type)) : 'Identity Document';
    }

    private function eventLabel(string $type): string
    {
        return match (true) {
            $type === 'transaction' => 'Transaction',
            str_starts_with($type, 'call_') => 'Call',
            str_starts_with($type, 'reminder') => 'Reminder',
            str_starts_with($type, 'comment') => 'Comment',
            str_starts_with($type, 'invoice') || str_starts_with($type, 'credit_note') => 'Finance',
            str_starts_with($type, 'service_request') => 'Service',
            str_contains($type, 'email') || str_contains($type, 'message') || str_contains($type, 'credentials') => 'Communication',
            in_array($type, self::ACCOUNT_CHANGE_TYPES, true) => 'Profile Update',
            $type === 'lead_created' => 'Registration',
            // An acronym, not a word: "KYC", never "Kyc".
            $type === 'kyc' || str_starts_with($type, 'kyc_') => 'KYC',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function eventStatus(ActivityLog $item): string
    {
        return match ($item->type) {
            'reminder_created' => 'Pending',
            'block', 'block_communications' => 'Blocked',
            default => 'Completed',
        };
    }

    /** Labels for the KYC audit trail; anything else is shown humanised. */
    /** Level events recorded while KYC had levels (NC-23); kept in the data, no longer shown (10 Oct 2026). */
    public const HIDDEN_KYC_EVENTS = ['level_requested', 'level_changed'];

    public const KYC_EVENT_LABELS = [
        'file_uploaded' => 'File uploaded', 'file_replaced' => 'File replaced', 'draft_saved' => 'Draft saved',
        'submitted' => 'Submitted', 'auto_approved' => 'Approved on submission (CRM)', 'review_started' => 'Review started',
        'document_approved' => 'Document approved', 'document_rejected' => 'Document rejected', 'document_pending' => 'Document reset to pending',
        'approved' => 'Approved', 'rejected' => 'Rejected', 'note_added' => 'Reviewer note', 'status_override' => 'Status set from the lead grid',
        'review_taken_over' => 'Review taken over',
        'document_acknowledged' => 'Document marked reviewed', 'document_unacknowledged' => 'Reviewed mark removed',
        'metadata_recorded' => 'Document details recorded', 'update_requested' => 'Update requested from the client',
    ];

    /** The KYC tab's inner views (NC-23.2); Overview is the default. */
    // The KYC tab is the overview cards and one request list (user, 10 Oct 2026); a request's documents
    // and audit trail open from it, in the request modal.
    public const KYC_VIEWS = ['history' => 'KYC History'];

    /** Request rows per page in KYC History (NC-23.3); 50 is the default. */
    public const KYC_REQUEST_SIZES = [10, 20, 50, 100];

    /** KYC History sort keys (?kh_sort=), all server-side over the whole result. */
    public const KYC_REQUEST_SORTS = ['reference', 'submitted', 'reviewed', 'expiry', 'status', 'risk'];

    /** KYC History status filters; qualifiers filter without rewriting the stored state. */
    public const KYC_REQUEST_STATUSES = [
        'approved' => 'Approved', 'pending' => 'Pending Review', 'under_review' => 'Under Review', 'rejected' => 'Rejected',
        'resubmitted' => 'Resubmitted', 'expired' => 'Expired', 'superseded' => 'Superseded',
    ];

    /** "1 file", "0 files", "2 documents" -- the one count wording every KYC view uses (NC-24.3). */
    /**
     * A request made from documents uploaded before requests had snapshots and an audit trail (NC-24.5):
     * the backfill created it, so what it never recorded stays "Not recorded", never filled in.
     */
    public static function kycIsLegacy(?\App\Models\KycSubmission $header): bool
    {
        return $header !== null && ($header->source === 'legacy_backfill' || str_starts_with((string) $header->provenance, 'legacy'));
    }

    public static function kycCount(int $count, string $noun): string
    {
        return $count.' '.($count === 1 ? $noun : $noun.'s');
    }

    /**
     * One request's bundle, counted once for every view (NC-24.3). A document is one logical
     * document (identity, proof of address); a file is one attached front/back/PDF reference;
     * a multi-page PDF is still one file, and a replaced file is not counted again (its old
     * version lives only in the audit trail). A required document counts as present only when
     * every side the policy needs is attached. These count references, not storage checks.
     *
     * @return array{documents: int, files: int, required: int, present: int, reviewed: int, label: string, progress: string}
     */
    public static function kycBundle(?\App\Models\KycSubmission $header, Collection $documents): array
    {
        $required = UserId::REQUIRED_TYPES;
        $files = $documents->sum(fn (UserId $d) => (int) filled($d->id_front) + (int) filled($d->id_back));
        // The client wizard's ID types say whether a back is needed (a PDF covers both); the CRM's
        // back is optional, and a client upload counts with either side, as its contract always has.
        $complete = function (UserId $d) use ($header) {
            if (blank($d->id_front) && blank($d->id_back)) {
                return false;
            }
            $needsBack = $d->type === 'id' && $header?->id_type && $header->source !== 'crm' && (\App\Models\KycSubmission::ID_TYPES[$header->id_type]['back'] ?? false) && $d->mime !== 'application/pdf';

            return ! $needsBack || (filled($d->id_front) && filled($d->id_back));
        };
        $requiredDocs = $documents->filter(fn (UserId $d) => in_array(strtolower((string) $d->type), $required, true));
        $present = collect($required)->filter(fn ($type) => $requiredDocs->contains(fn (UserId $d) => strtolower((string) $d->type) === $type && $complete($d)))->count();
        // An incomplete document is never counted as reviewed, even if it was ticked.
        $reviewed = collect($required)->filter(fn ($type) => $requiredDocs->contains(fn (UserId $d) => strtolower((string) $d->type) === $type && $complete($d) && $d->acknowledged_at))->count();

        return [
            'documents' => $documents->count(),
            'files' => $files,
            'required' => count($required),
            'present' => $present,
            'reviewed' => $reviewed,
            'label' => self::kycCount($documents->count(), 'document').' · '.self::kycCount($files, 'file'),
            'progress' => $present.'/'.count($required).' required '.(count($required) === 1 ? 'document' : 'documents').' present · '.$reviewed.'/'.count($required).' reviewed · '.self::kycCount($files, 'file'),
        ];
    }

    /** How the history labels a stored state (NC-23.1: pending_approval reads "Pending Review"). */
    public static function kycStateLabel(string $state): string
    {
        return $state === \App\Models\KycSubmission::PENDING_APPROVAL ? 'Pending Review' : (\App\Models\KycSubmission::LABELS[$state] ?? ucfirst($state));
    }

    /**
     * One row per submitted KYC request of this lead (NC-23.1/23.3), filtered, searched and
     * sorted on the server over the whole set, with the five summary cards computed from the
     * same filtered cohort before paging. Drafts are not requests yet and stay out. Values the
     * platform never recorded are null; nothing is inferred.
     *
     * @return array{rows: Collection, cards: array, errors: array, excluded: int, types: array, sort: string, dir: string, filters: array}
     */
    public function kycRequestList(User $lead, Request $request): array
    {
        $headers = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->with('reviewer')->get();
        $byId = $headers->keyBy('id');
        $documents = UserId::query()->where('user_id', $lead->id)->get()->groupBy('attempt');
        $today = now('UTC')->startOfDay();

        $all = $headers->filter(fn ($h) => $h->state !== \App\Models\KycSubmission::DRAFT || $h->submitted_at)
            ->map(fn ($h) => $this->kycRequestRow($h, $documents->get($h->attempt, collect()), $byId, $today))->values();

        $search = mb_strtolower(trim((string) $request->input('kh_search')));
        $statuses = array_values(array_intersect((array) $request->input('kh_status', []), array_keys(self::KYC_REQUEST_STATUSES)));
        $types = array_values(array_filter((array) $request->input('kh_type', []), 'is_string'));
        $errors = [];
        $from = $this->kycDate($request->input('kh_from'));
        $to = $this->kycDate($request->input('kh_to'));
        if ($from && $to && $from->gt($to)) {
            $errors['kh_to'] = 'The end date is before the start date; showing all dates.';
            $from = $to = null;
        }

        $dated = $from || $to;
        $excluded = $dated ? $all->whereNull('submitted_at')->count() : 0;
        $cohort = $all->filter(function (array $row) use ($search, $types, $from, $to, $dated) {
            if ($search !== '' && ! str_contains($row['haystack'], $search)) {
                return false;
            }
            if ($dated && (! $row['submitted_at'] || ($from && $row['submitted_at']->lt($from)) || ($to && $row['submitted_at']->gt($to->copy()->endOfDay())))) {
                return false;
            }

            return ! $types || array_intersect($types, $row['type_labels']);
        })->values();
        // The cards count the cohort before the status filter: they are that filter's buttons, so
        // choosing one must not zero the others.
        $rows = $cohort->filter(fn (array $row) => ! $statuses || collect($statuses)->contains(fn ($status) => match ($status) {
            'pending' => in_array($row['state'], [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW], true),
            'resubmitted' => $row['resubmitted'],
            'expired' => $row['expired'],
            default => $row['state'] === $status,
        }))->values();

        $sort = in_array($request->input('kh_sort'), self::KYC_REQUEST_SORTS, true) ? $request->input('kh_sort') : 'submitted';
        $dir = $request->input('kh_dir') === 'asc' ? 'asc' : 'desc';
        $key = fn (array $row) => match ($sort) {
            'reference' => $row['reference'],
            'submitted' => $row['submitted_at']?->format('Y-m-d H:i:s'),
            'reviewed' => $row['reviewed_at']?->format('Y-m-d H:i:s'),
            'expiry' => $row['expiry']?->format('Y-m-d'),
            'status' => $row['state_label'],
            'risk' => $row['risk_score'] === null ? null : sprintf('%08.2f', $row['risk_score']),
        };
        // Nulls last whichever way it sorts; the request UUID breaks ties so paging is stable.
        $rows = $rows->sort(function (array $a, array $b) use ($key, $dir) {
            [$x, $y] = [$key($a), $key($b)];
            if ($x === null || $y === null) {
                return $x === $y ? strcmp($a['uuid'], $b['uuid']) : ($x === null ? 1 : -1);
            }
            $order = strnatcasecmp((string) $x, (string) $y);

            return ($dir === 'desc' ? -$order : $order) ?: strcmp($a['uuid'], $b['uuid']);
        })->values();

        $total = $cohort->count();
        $percent = fn (int $n) => $total ? (int) round($n / $total * 100) : 0;
        $cohortDocuments = $cohort->flatMap(fn ($row) => $row['documents'])->unique('id');
        $count = [
            'approved' => $cohort->where('state', \App\Models\KycSubmission::APPROVED)->count(),
            'pending' => $cohort->whereIn('state', [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW])->count(),
            'rejected' => $cohort->filter(fn ($row) => $row['state'] === \App\Models\KycSubmission::REJECTED || $row['resubmitted'])->count(),
        ];

        return [
            'rows' => $rows,
            'cards' => [
                'total' => $total,
                'this_year' => $cohort->filter(fn ($row) => $row['submitted_at']?->year === now()->year)->count(),
                'approved' => $count['approved'], 'approved_pct' => $percent($count['approved']),
                'pending' => $count['pending'], 'pending_pct' => $percent($count['pending']),
                'rejected' => $count['rejected'], 'rejected_pct' => $percent($count['rejected']),
                // Documents, not file sides: the front and back of one ID count once.
                'expired' => $cohortDocuments->filter(fn (UserId $d) => $d->document_expiry && $d->document_expiry->lt($today))->count(),
                'no_expiry' => $cohortDocuments->filter(fn (UserId $d) => ! $d->document_expiry)->count(),
            ],
            'errors' => $errors,
            'excluded' => $excluded,
            'types' => $all->flatMap(fn ($row) => $row['type_labels'])->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
            'sort' => $sort,
            'dir' => $dir,
            'filters' => ['search' => (string) $request->input('kh_search', ''), 'from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'status' => $statuses, 'type' => $types],
        ];
    }

    /** One request row; every document column summarises the request's bundle. */
    private function kycRequestRow(\App\Models\KycSubmission $header, Collection $documents, Collection $byId, Carbon $today): array
    {
        $identity = $documents->firstWhere('type', 'id');
        $primary = $identity ?? $documents->first();
        $proof = $documents->firstWhere('type', 'proof_of_address');
        $legacy = $header->personal ?? [];
        // Compatibility adapter: details a legacy snapshot carried, used only when the document has none.
        $last4 = $primary?->document_number_last4 ?? (filled($legacy['document_number'] ?? null) ? mb_substr(preg_replace('/\s+/', '', (string) $legacy['document_number']), -4) : null);
        $countryCode = $primary?->issuing_country ?? (filled($legacy['issuing_country'] ?? null) ? Geo::find($legacy['issuing_country'])['code'] : null);
        $expiry = $primary?->document_expiry ?? $this->kycDate($legacy['document_expiry'] ?? null);
        $previous = $header->previous_submission_id ? $byId->get($header->previous_submission_id) : null;
        $labelOf = fn (UserId $d) => $d->document_label ?: $d->typeLabel();
        $files = $documents->sum(fn (UserId $d) => (int) filled($d->id_front) + (int) filled($d->id_back));
        $expired = $documents->contains(fn (UserId $d) => $d->document_expiry && $d->document_expiry->lt($today))
            || (! $primary?->document_expiry && $expiry && $expiry->lt($today));
        $reviewer = $header->reviewer_id ? ($header->reviewer?->name ?? 'Removed user') : null;
        $country = $countryCode ? ['name' => $this->countryName($countryCode)] : null;

        $row = [
            'uuid' => $header->id,
            'reference' => $header->reference,
            'attempt' => $header->attempt,
            'submitted_at' => $header->submitted_at,
            'provenance' => $header->provenance,
            'primary' => $primary ? $labelOf($primary) : 'Not provided',
            'doc_count' => $documents->count(),
            'file_count' => $files,
            'bundle' => self::kycBundle($header, $documents),
            'proof' => $proof ? ($proof->document_label ?: 'Proof of Address') : 'Not provided',
            'number_last4' => $last4,
            'country_code' => $countryCode ? strtolower($countryCode) : null,
            'country_name' => $country['name'] ?? null,
            'state' => $header->state,
            'state_label' => self::kycStateLabel($header->state),
            'legacy' => self::kycIsLegacy($header),
            'resubmitted' => $previous?->state === \App\Models\KycSubmission::REJECTED,
            'expired' => $expired,
            'reviewer' => $reviewer,
            'reviewed_at' => $header->reviewed_at,
            'expiry' => $expiry,
            'risk_score' => $header->risk_score !== null ? (float) $header->risk_score : null,
            'risk_band' => $header->risk_band,
            'note' => $header->public_note,
            'documents' => $documents->values(),
            'type_labels' => $documents->map($labelOf)->unique()->values()->all(),
        ];
        $row['haystack'] = mb_strtolower(implode(' ', array_filter([$row['reference'], $row['primary'], $row['proof'], implode(' ', $row['type_labels']),
            $documents->map(fn (UserId $d) => $d->typeLabel())->implode(' '), $reviewer, $header->public_note, $last4])));

        return $row;
    }

    /** The catalogue's name for an ISO code (Geo::find echoes its input back as the name). */
    private function countryName(string $code): string
    {
        return collect(Geo::COUNTRIES)->search(fn ($row) => $row[0] === strtolower($code)) ?: strtoupper($code);
    }

    private function kycDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, 'UTC')->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** What an event says, in words: a step, a reason. Internal notes stay internal-only views. */
    private function kycEventDetail(\App\Models\KycEvent $event): ?string
    {
        $data = $event->data ?? [];

        return match ($event->type) {
            'draft_saved' => isset($data['step']) ? ucfirst($data['step']).' step' : null,
            'file_uploaded', 'file_replaced' => trim(($data['side'] ?? '').' '.($data['name'] ?? '')) ?: null,
            'metadata_recorded' => collect([$data['number'] ?? null, $data['expiry'] ?? null, $data['country'] ?? null])->filter()->implode(' · ') ?: null,
            'update_requested' => $data['reason'] ?? null,
            // The reason the client was given; internal notes stay in the Notes view only.
            'rejected' => $data['reason'] ?? null,
            'document_rejected' => $data['note'] ?? null,
            'status_override' => 'Set to '.($data['status'] ?? 'unknown'),
            default => null,
        };
    }

    /**
     * One KYC document for the read-only "Document details" viewer (NC-23.4, frame 4228:30862):
     * its file sides, the facts recorded with its request and what happened to it. Historical
     * facts come from the request's snapshot -- the submitted name and address apply to every
     * document of the request, not today's profile. A detail never captured is "Not recorded";
     * no check is claimed that did not run, and nothing here can change a review.
     */
    public function kycDocument(User $lead, string $documentId, string $side): ?array
    {
        $document = UserId::query()->where('user_id', $lead->id)->with('reviewer')->find($documentId);
        if (! $document) {
            return null;
        }

        $header = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->where('attempt', $document->attempt)->first();
        $sides = array_keys(array_filter(['front' => filled($document->id_front), 'back' => filled($document->id_back)]));
        $side = in_array($side, $sides, true) ? $side : ($sides[0] ?? 'front');
        $key = (string) ($side === 'back' ? $document->id_back : $document->id_front);
        $extension = strtolower(pathinfo((string) parse_url($key, PHP_URL_PATH), PATHINFO_EXTENSION));
        $isPdf = $side === 'front' && $document->mime ? $document->mime === 'application/pdf' : $extension === 'pdf';
        $personal = $header?->personal ?? [];
        $address = $header?->address ?? [];
        $fileUrl = fn (bool $download = false) => route('admin.leadmax.leads.kyc.file', ['id' => $lead->id, 'document' => $document->id, 'side' => $side]).($download ? '?download=1' : '');
        $storageKey = (string) Kyc::storageKey($key);
        $isIdentity = $document->type === 'id';
        // Document metadata first; a legacy snapshot's values only fill in for the identity document.
        $number = $document->document_number_last4 ?? ($isIdentity && filled($personal['document_number'] ?? null) ? mb_substr(preg_replace('/\s+/', '', (string) $personal['document_number']), -4) : null);
        $country = $document->issuing_country ?? ($isIdentity && filled($personal['issuing_country'] ?? null) ? Geo::find($personal['issuing_country'])['code'] : null);
        $expiry = $document->document_expiry ?? ($isIdentity ? $this->kycDate($personal['document_expiry'] ?? null) : null);
        $name = $document->original_name ?: basename((string) parse_url($key, PHP_URL_PATH));

        $events = $header
            ? \App\Models\KycEvent::withoutGlobalScopes()->where('submission_id', $header->id)->whereNotIn('type', self::HIDDEN_KYC_EVENTS)
                ->where(fn ($q) => $q->whereNull('document_id')->orWhere('document_id', $document->id))
                ->with('actor')->orderBy('id')->get()
            : collect();

        return [
            'document' => $document,
            'header' => $header,
            'side' => $side,
            'sides' => $sides,
            'isPdf' => $isPdf,
            'previewUrl' => $fileUrl(),
            'downloadUrl' => $fileUrl(true),
            'available' => $available = $storageKey !== '' && \Illuminate\Support\Facades\Storage::disk('s3')->exists($storageKey),
            // A PDF's pages, for "Page N of M" (0 when its structure hides them: no page controls then).
            'pages' => $isPdf && $available ? \App\Rules\KycFile::pdfPagesIn((string) \Illuminate\Support\Facades\Storage::disk('s3')->get($storageKey)) : 0,
            'version' => $document->sha256 ? substr($document->sha256, 0, 12) : null,
            'fileType' => $isPdf ? 'PDF' : (strtoupper($extension) ?: 'File'),
            'name' => $name,
            'status' => strtolower((string) ($document->status ?: UserId::PENDING)),
            // A decided request's documents are history; an open request's are still under its review.
            'historical' => (bool) $header?->isFinal(),
            'reference' => $header?->reference,
            // Frame 4228:30862's metadata, in its order; anything not captured shows "Not recorded".
            'fields' => [
                'Full name (KYC submission)' => trim(($personal['first_name'] ?? '').' '.($personal['last_name'] ?? '')) ?: null,
                // The submitted address belongs to the request, so it shows for a passport as well as a bill.
                'Address (KYC submission)' => $address
                    ? (collect([$address['street'] ?? null, $address['city'] ?? null, $address['postcode'] ?? null, $address['country'] ?? null])->filter()->implode(', ') ?: null)
                    : null,
                'Issuing Country' => $country ? $this->countryName($country) : null,
                'Document name' => $name ?: null,
                'Type' => $document->document_label ?: $document->typeLabel(),
                'Document Number' => $number ? '••••'.$number : null,
                'Expiry Date' => $expiry?->format('Y-m-d'),
                'Submitted' => ($header?->submitted_at ?? $document->created_at)?->format('Y-m-d H:i'),
                'Reviewed' => $document->reviewed_at?->format('Y-m-d H:i'),
                'Reviewer' => $document->reviewed_by ? ($document->reviewer?->name ?? 'Removed user') : null,
            ],
            'countryCode' => $country ? strtolower($country) : null,
            'details' => array_filter([
                'File' => $document->mime ? $document->mime.($document->size ? ' · '.number_format($document->size / 1024, 0).' KB' : '') : null,
                'Malware scan' => $document->scan_state === 'not_scanned' ? 'Not scanned (no scanner configured)' : null,
            ], fn ($value) => filled($value)),
            'trail' => $events->map(fn (\App\Models\KycEvent $event) => [
                'label' => self::KYC_EVENT_LABELS[$event->type] ?? ucfirst(str_replace('_', ' ', $event->type)),
                'actor' => $event->actor?->name ?? 'System',
                'role' => $event->actor_role,
                'at' => $event->created_at,
                'note' => $event->data['reason'] ?? $event->data['note'] ?? $event->data['name'] ?? null,
            ]),
        ];
    }

    /**
     * What the Add KYC / New KYC wizard opens with (NC-13/14). An open draft resumes with its
     * saved values and files; otherwise the lead's own details are offered as a starting point
     * -- what is saved stays a snapshot and never changes the lead. A submission waiting for or
     * under review blocks a second one.
     */
    public function kycWizard(User $lead): array
    {
        $header = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->orderByDesc('attempt')->first();
        $blocked = in_array($header?->state, [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW], true);
        $draft = $header?->state === \App\Models\KycSubmission::DRAFT ? $header : null;
        $documents = $draft ? UserId::query()->where('user_id', $lead->id)->where('attempt', $draft->attempt)->get()->keyBy('type') : collect();
        $phone = Phone::parts($lead->phone, $lead->country);
        // The catalogue's own name for the lead's country (aliases and ISO codes resolve too).
        $code = Geo::find($lead->country)['code'];
        $country = $code ? (collect(Geo::COUNTRIES)->search(fn ($row) => $row[0] === $code) ?: null) : null;
        $inCatalog = fn (?string $name) => $name && array_key_exists($name, Geo::COUNTRIES) ? $name : null;
        $personal = $draft?->personal ?? [];
        $address = $draft?->address ?? [];
        $id = $documents->get('id');
        $proof = $documents->get('proof_of_address');

        return [
            'blocked' => $blocked,
            'version' => $draft?->version ?? 0,
            'personal' => [
                'first_name' => $personal['first_name'] ?? $lead->first_name,
                'last_name' => $personal['last_name'] ?? $lead->last_name,
                'single_name' => (bool) ($personal['single_name'] ?? false),
                'birthdate' => $personal['birthdate'] ?? ($lead->birthdate ? \Carbon\Carbon::parse($lead->birthdate)->toDateString() : null),
                'nationality' => $personal['nationality'] ?? $inCatalog($country),
                'dial_code' => $personal['dial_code'] ?? $phone['dial'],
                'phone' => $personal['phone'] ?? $phone['national'],
            ],
            'id_type' => $draft?->id_type,
            'id_type_other' => $draft?->id_type === 'other' ? $id?->document_label : null,
            // NC-23: the ID's optional details.
            'metadata' => [
                'document_number' => $id?->document_number,
                'document_expiry' => $id?->document_expiry?->toDateString(),
                'issuing_country' => $id?->issuing_country ? (collect(Geo::COUNTRIES)->search(fn ($row) => $row[0] === strtolower($id->issuing_country)) ?: null) : null,
            ],
            'files' => [
                'id_front' => $id?->id_front ? $id->original_name : null,
                'id_back' => $id?->id_back ? 'Back uploaded' : null,
                'address_front' => $proof?->id_front ? $proof->original_name : null,
                'address_back' => $proof?->id_back ? 'Additional page uploaded' : null,
            ],
            'address' => [
                'street' => $address['street'] ?? $lead->address,
                'city' => $address['city'] ?? $lead->city,
                'postcode' => $address['postcode'] ?? $lead->zip_code,
                'country' => $address['country'] ?? $inCatalog($country),
                'document_type' => $address['document_type'] ?? 'utility_bill',
                'document_type_other' => $address['document_type_other'] ?? null,
            ],
            // Resume where the draft left off.
            'step' => ! $draft || ! $draft->personal ? 'personal' : (! $id ? 'identity' : 'address'),
            'countries' => array_keys(Geo::COUNTRIES),
            'dialCodes' => collect(Phone::codes())->unique()->sort(SORT_NATURAL)->values()->all(),
        ];
    }

    /** The public reason and internal reviewer notes of one KYC attempt, for the Notes dialog. */
    public function kycNotes(User $lead, string $submissionId): ?array
    {
        $header = \App\Models\KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->find($submissionId);
        if (! $header) {
            return null;
        }

        // Internal notes only: a document rejection's note and a rejecting override's note are
        // the reasons the client is shown, so they belong with the public reason, not here.
        $notes = \App\Models\KycEvent::withoutGlobalScopes()->where('submission_id', $header->id)
            ->whereIn('type', ['note_added', 'approved', 'rejected', 'status_override'])
            ->with('actor')->orderBy('id')->get()
            ->filter(fn (\App\Models\KycEvent $event) => filled($event->data['note'] ?? null)
                && ! ($event->type === 'status_override' && ($event->data['status'] ?? null) === 'rejected'))
            ->map(fn (\App\Models\KycEvent $event) => ['note' => $event->data['note'], 'actor' => $event->actor?->name ?? 'System', 'role' => $event->actor_role, 'at' => $event->created_at])
            ->values();

        return ['header' => $header, 'publicReason' => $header->public_note, 'notes' => $notes];
    }

    /**
     * Everything recorded about one history row, for the read-only detail dialog. Only rows
     * belonging to this lead are found; nothing is invented when a value was never recorded.
     */
    public function historyEvent(User $lead, string $kind, string $id): ?array
    {
        $name = fn (?string $userId) => $userId ? (User::withoutGlobalScopes()->find($userId)?->name ?? 'Unknown user') : null;
        // e.g. "Nigeria (NG) · DB-IP Lite, looked up 2026-10-10 14:03": where the country came from, and when.
        $countryText = fn (array $c) => $c['country'].($c['country_code'] ? ' ('.$c['country_code'].')' : '').($c['country_note'] ? ' · '.$c['country_note'] : '');

        if ($kind === 'login') {
            $login = LoginHistory::query()->where('user_id', $lead->id)->find($id);
            if (! $login) {
                return null;
            }
            $previous = LoginHistory::query()->where('user_id', $lead->id)->where('created_at', '<', $login->created_at)->latest()->first();

            return ['title' => $login->status === 'failed' ? 'Failed login attempt' : 'Successful login', 'at' => $login->created_at, 'fields' => array_filter([
                'Actor' => $lead->name.' (Client)',
                'Affected account' => $lead->account_id ?: $lead->email,
                'Result' => $login->status === 'failed' ? 'Failed' : 'Success',
                'IP address' => $login->ip_address,
                'Country' => $countryText($this->ipCountry($login->location, null, $login->ip_address, $login->location ? IpCountry::SOURCE : null, $login->created_at?->toIso8601String())),
                'Browser' => $login->browser ?: 'Unknown',
                'Device' => $this->deviceLabel($login->device_type, $login->platform),
                'Previous login' => $previous ? $previous->created_at->format('Y-m-d H:i').' · '.($previous->ip_address ?: 'IP not recorded').' · '.$this->deviceLabel($previous->device_type, $previous->platform) : null,
            ], fn ($value) => $value !== null && $value !== '')];
        }

        $item = ActivityLog::query()->where('user_id', $lead->id)->find($id);
        if (! $item) {
            return null;
        }
        $info = is_array($item->info) ? $item->info : ['description' => (string) $item->info];
        $known = ['actor', 'actor_id', 'actor_role', 'ip', 'user_agent', 'browser', 'platform', 'device', 'country', 'country_code', 'country_source', 'country_at', 'impersonator_id', 'initiator_id', 'initiator_role', 'description'];
        // Anything else the writer recorded (old/new values, amounts, references), minus secrets.
        $extra = collect($info)->except($known)->reject(fn ($value, $key) => str_contains(strtolower((string) $key), 'password') || str_contains(strtolower((string) $key), 'token'))
            ->map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->filter(fn ($value) => $value !== '')
            ->mapWithKeys(fn ($value, $key) => [ucfirst(str_replace('_', ' ', (string) $key)) => $value])->all();

        return ['title' => $item->title, 'at' => $item->created_at, 'fields' => array_filter([
            'Event' => $this->eventLabel($item->type),
            'Actor' => ($info['actor'] ?? 'System').' ('.($info['actor_role'] ?? 'role not recorded').')',
            'Impersonated by' => $name($info['impersonator_id'] ?? null),
            'Initiated by' => isset($info['initiator_id']) ? $name($info['initiator_id']).' ('.($info['initiator_role'] ?? 'role not recorded').')' : null,
            'Affected account' => $lead->name.' · '.($lead->account_id ?: $lead->email),
            'Result' => $this->eventStatus($item),
            'Description' => $info['description'] ?? null,
            'IP address' => $info['ip'] ?? null,
            'Country' => $countryText($this->ipCountry($info['country'] ?? null, $info['country_code'] ?? null, $info['ip'] ?? null, $info['country_source'] ?? null, $info['country_at'] ?? null)),
            'Browser' => ($info['browser'] ?? null) ?: ($this->shortAgent($info['user_agent'] ?? null) ?? 'Unknown'),
            'Device' => $this->deviceLabel($info['device'] ?? null, $info['platform'] ?? null),
            'User agent' => $info['user_agent'] ?? null,
            ...$extra,
        ], fn ($value) => $value !== null && $value !== '')];
    }

    /** "Desktop · Windows", "Mobile", or "Unknown" when nothing was recorded. */
    /**
     * Country of a history row: what was recorded when it was written, otherwise the stored IP looked
     * up now (DB-IP Lite) and marked as such, otherwise Unknown. Nothing is guessed from the profile.
     *
     * @return array{country: string, country_code: ?string, country_note: ?string}
     */
    private function ipCountry(?string $name, ?string $code, ?string $ip, ?string $source = null, ?string $at = null): array
    {
        if (filled($name) && $name !== 'Unknown') {
            $when = $at ? Carbon::parse($at)->format('Y-m-d H:i') : null;

            return ['country' => $name, 'country_code' => $code ?: Geo::find($name)['code'], 'country_note' => $source ? $source.($when ? ', looked up '.$when : '') : null];
        }
        $found = app(IpCountry::class)->lookup($ip);

        return $found
            ? ['country' => $found['name'], 'country_code' => $found['code'], 'country_note' => IpCountry::SOURCE.', looked up when shown (not recorded at the time)']
            : ['country' => 'Unknown', 'country_code' => null, 'country_note' => null];
    }

    private function deviceLabel(?string $device, ?string $platform): string
    {
        return implode(' · ', array_filter([$device ? ucfirst($device) : null, $platform ?: null])) ?: 'Unknown';
    }

    private function shortAgent(?string $agent): ?string
    {
        if (! $agent) {
            return null;
        }
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $needle => $name) {
            if (preg_match('#'.$needle.'/([\d]+)#', $agent, $m)) {
                return $name.'/'.$m[1].'.0';
            }
        }

        return \Illuminate\Support\Str::limit($agent, 24);
    }
}
