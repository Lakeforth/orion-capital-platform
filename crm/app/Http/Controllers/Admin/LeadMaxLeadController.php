<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\KycConflict;
use App\Models\ActivityLog;
use App\Models\KycSubmission;
use App\Models\LeadInvoice;
use App\Models\LeadSignal;
use App\Models\LeadView;
use App\Models\Note;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Models\UserId;
use App\Rules\KycFile;
use App\Services\GoogleDrive\GoogleDriveService;
use App\Services\LeadMax\LeadCapture;
use App\Services\LeadMax\LeadDetailTabs;
use App\Http\Controllers\Admin\LeadMaxToolsController;
use App\Services\KycLifecycle;
use App\Support\LeadMax\ActivityContext;
use App\Support\LeadMax\Geo;
use App\Support\LeadMax\LeadFields;
use App\Support\Kyc;
use App\Support\KycWizard;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeadMaxLeadController extends LeadMaxController
{
    public const STATUSES = ['new', 'contacted', 'qualified', 'converted', 'disqualified'];

    /** Bulk actions on the leads list. 'stage' is the new/contacted/... pipeline, 'lead_status' the company's call-outcome list. */
    public const BULK_ACTIONS = ['assign', 'lead_status', 'stage', 'delete'];

    public const BULK_MAX = 5000;

    /** Filter / bulk value for leads whose lead status is not set yet. */
    public const LEAD_STATUS_NONE = '__none';

    /** The leads list shows every lead unless a period is picked: most tenants' leads are older than the current week. */
    public const LIST_PERIOD = 'all_time';

    /** Query keys that narrow the leads list; with any of them set, the bulk bar acts on every matching lead. */
    public const LIST_FILTERS = ['search', 'lead_status', 'status', 'country', 'source', 'created_by', 'manager_id', 'ftd', 'kyc', 'comment_from', 'comment_to'];

    /** Rows per page the list offers. Anything else falls back to the default. */
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    /** Sortable columns with the label the Sort By control shows for each. */
    public const SORT_LABELS = [
        'account_id' => 'Lead ID',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'phone' => 'Phone',
        'email' => 'Email',
        'label' => 'Lead status',
        'status' => 'Stage',
        'last_comment_at' => 'Last comment',
        'country' => 'Country',
        'source' => 'Source',
        'campaign' => 'Campaign',
        'desk' => 'Call centre',
        'platform' => 'Platform type',
        'created_at' => 'Registration date',
    ];

    /**
     * Columns the leads table may be sorted by, keyed to the header arrows.
     *
     * Every entry is a real column on the query — 'last_comment_at' is the withMax alias — so
     * none of these needs a join. Agent and Affiliate are relations and are deliberately absent:
     * sorting those means joining users/affiliates, which is a bigger change than it looks.
     */
    public const SORTABLE = ['account_id', 'first_name', 'last_name', 'phone', 'email', 'label', 'status', 'last_comment_at', 'country', 'source', 'campaign', 'desk', 'platform', 'created_at', 'agent', 'affiliate'];

    /** Agent and Affiliate sort by that person's name; the lead column that points at them. */
    private const PERSON_SORTS = ['agent' => 'manager_id', 'affiliate' => 'created_by'];

    public const DETAIL_TABS = ['comments', 'finances', 'trading', 'kyc', 'history', 'calls', 'chat', 'signals', 'sessions'];

    public const DETAIL_DIALOGS = ['assign', 'lead-status', 'change-password', 'delete', 'email', 'message', 'payment', 'refund', 'withdrawal', 'service', 'service-history', 'cancel-service', 'invoice', 'credit-note', 'escalate', 'assign-support', 'ticket-priority', 'signal', 'signal-close', 'history-event', 'adjustment', 'kyc-document', 'kyc-notes', 'kyc-new', 'kyc-request'];

    private function staff(): Builder
    {
        return User::query()->whereHasRole(['admin', 'tenant_admin', 'manager', 'agent']);
    }

    private function lead(string $id): User
    {
        return $this->leadQuery()->findOrFail($id);
    }

    private function filtered(Request $request, $from, $to): Builder
    {
        $query = $this->filterSearch($this->leadQuery()->whereBetween('created_at', [$from, $to]), $request);
        $this->applyLeadFilters($query, $request, ['country', 'source', 'created_by', 'manager_id', 'status', 'ftd']);
        // KYC state, the same values the lead page's KYC status field offers.
        $kyc = $this->filterValues($request, 'kyc');
        if ($kyc !== []) {
            $column = $query->getModel()->qualifyColumn('verification_status');
            count($kyc) === 1 ? $query->where($column, $kyc[0]) : $query->whereIn($column, $kyc);
        }

        // Commented between two dates. last_comment_at is a withMax alias added for the list, so
        // it cannot be filtered on here -- this asks the notes themselves instead.
        $commentFrom = $request->date('comment_from');
        $commentTo = $request->date('comment_to');
        if ($commentFrom || $commentTo) {
            $query->whereHas('notes', function ($q) use ($commentFrom, $commentTo) {
                if ($commentFrom) {
                    $q->where('created_at', '>=', $commentFrom->copy()->startOfDay());
                }
                if ($commentTo) {
                    $q->where('created_at', '<=', $commentTo->copy()->endOfDay());
                }
            });
        }

        // Lead status is multi-select, and "Not set" is a real choice that can be picked next to
        // named statuses -- so it becomes an OR branch rather than an either/or against them.
        $leadStatuses = $this->filterValues($request, 'lead_status');
        if ($leadStatuses !== []) {
            $label = $query->getModel()->qualifyColumn('label');
            $unset = in_array(self::LEAD_STATUS_NONE, $leadStatuses, true);
            $named = array_values(array_diff($leadStatuses, [self::LEAD_STATUS_NONE]));
            $query->where(function ($q) use ($label, $named, $unset) {
                if ($named !== []) {
                    count($named) === 1 ? $q->where($label, $named[0]) : $q->whereIn($label, $named);
                }
                if ($unset) {
                    $blank = fn ($inner) => $inner->whereNull($label)->orWhere($label, '');
                    $named !== [] ? $q->orWhere($blank) : $q->where($blank);
                }
            });
        }

        return $query;
    }

    /**
     * The company's lead statuses (No answer, Call back, ...): the same editable list the main CRM
     * uses for users.label (Data type 'status', managed under Data > Leads), in the order it was set up.
     */
    private function leadStatuses(): array
    {
        return \App\Models\Data::query()->where('type', 'status')->where('is_active', true)->orderBy('id')
            ->pluck('name')->map(fn ($name) => trim((string) $name))->filter()->unique()->values()->all();
    }

    private function counts(Builder $query): array
    {
        // Every status count in one aggregate query (mirrors statusQuery()).
        $row = (clone $query)->toBase()->selectRaw("COUNT(*) as total,
            SUM(CASE WHEN status IS NULL OR status IN ('', 'new', 'pending') THEN 1 ELSE 0 END) as c_new,
            SUM(CASE WHEN status IN ('disqualified', 'rejected') THEN 1 ELSE 0 END) as c_disqualified".collect(self::STATUSES)
            ->reject(fn ($status) => in_array($status, ['new', 'disqualified'], true))
            ->map(fn ($status) => ", SUM(CASE WHEN status = '".$status."' THEN 1 ELSE 0 END) as c_".$status)->implode(''))->first();
        $counts = ['total' => (int) $row->total];
        foreach (self::STATUSES as $status) {
            $counts[$status] = (int) $row->{'c_'.$status};
        }

        return $counts;
    }

    /**
     * The Leads list. With $incoming it is Attribution's Incoming Leads: the same canonical list,
     * filters and pagers, newest arrival first by default (lead_attributions.received_at).
     */
    public function index(Request $request, bool $incoming = false)
    {
        [$from, $to] = $this->period($request, self::LIST_PERIOD);
        $base = $this->filtered($request, $from, $to);
        if ($incoming && $request->input('unread') === '1') {
            LeadView::unread($base, (string) auth()->id());
        }
        // The cards are a second layer of filter over the advanced filter, not a view beside it:
        // with an agent selected they must count that agent's leads. So the metric request keeps
        // every filter and drops only 'status' — the stage is what the cards themselves select,
        // and leaving it in would make each card count only its own stage.
        $metricRequest = clone $request;
        $metricRequest->query->remove('status');
        $metricBase = $this->filtered($metricRequest, $from, $to);
        $duration = $from->diffInSeconds($to) + 1;
        $previous = $this->counts($this->filtered($metricRequest, $from->copy()->subSeconds($duration), $from->copy()->subSecond()));
        $stats = $this->counts($metricBase);
        // Columns the header arrows may sort by. 'label' is the Lead Status column and
        // 'last_comment_at' the withMax alias added below — both are real selected columns, so
        // ordering on them needs no join. Agent and Affiliate order by the person's name (personName).
        // Rows per page, whitelisted: it reaches paginate() straight off the query string.
        $perPage = in_array((int) $request->input('per_page'), self::PER_PAGE_OPTIONS, true)
            ? (int) $request->input('per_page')
            : self::PER_PAGE;
        $sortable = $incoming ? [...self::SORTABLE, 'received_at'] : self::SORTABLE;
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : ($incoming ? 'received_at' : 'account_id');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $selected = $request->filled('lead') ? $this->lead($request->input('lead'))->load(['affiliate', 'manager']) : null;
        $activity = $selected ? ActivityLog::query()->where('user_id', $selected->id)->latest()->limit(100)->get() : collect();
        $preferred = company_settings('leadmax_preferred_owners', []);
        $members = $this->staff()->with('roles')->orderBy('first_name')->get()->sortBy(fn ($member) => (($position = array_search($member->id, $preferred, true)) !== false ? $position : count($preferred)))->values();
        $notes = $selected ? $selected->notes()->with('by')->latest()->get() : collect();

        $view = view($request->header('X-LeadMax-Fragment') === 'leads' ? 'admin.leadmax.partials.leads-content' : ($incoming ? 'admin.leadmax.attribution-incoming' : 'admin.leadmax.leads'), [
            ...$this->common($request, $incoming ? 'attribution' : 'leads'), 'stats' => $stats, 'previousStats' => $previous,
            'listRouteName' => $incoming ? 'admin.leadmax.attribution' : 'admin.leadmax.leads',
            // Attribution heads the list with its own title and tabs, also when the list is re-fetched.
            'hideLeadsHeading' => $incoming,
            // The list only needs each lead's most recent comment date, so take the max rather than
            // loading every note on every row -- and notes() has no order, so ->first() was handing
            // back whichever row the database happened to return, usually the oldest.
            'leads' => $leads = $base->with(['affiliate', 'manager'])->withMax('notes as last_comment_at', 'created_at')
                ->when($incoming, fn ($query) => $query->addSelect(['received_at' => \App\Models\LeadAttribution::withoutGlobalScopes()->select('received_at')->whereColumn('lead_attributions.lead_id', 'users.id')->limit(1)]))
                ->when($sort === 'account_id', fn ($query) => $query->orderByRaw('CAST(account_id AS DECIMAL(20,0)) '.$direction), fn ($query) => isset(self::PERSON_SORTS[$sort])
                    ? $query->orderBy($this->personName(self::PERSON_SORTS[$sort], 'first_name'), $direction)->orderBy($this->personName(self::PERSON_SORTS[$sort], 'last_name'), $direction)
                    : $query->orderBy($sort, $direction))->orderByDesc('id')->paginate($perPage)->withQueryString(),
            // Leads on this page the viewer has not opened yet (red dot in the Lead ID cell).
            'unreadLeadIds' => LeadView::unread(User::query()->whereKey($leads->pluck('id')), (string) auth()->id())->pluck('id')->flip()->all(),
            'incomingUnread' => $incoming ? LeadView::unreadCount($request->user()) : null,
            'selected' => $selected, 'activity' => $activity, 'notes' => $notes,
            'members' => $members,
            'affiliates' => User::query()->whereHasRole('affiliate')->orderBy('first_name')->get(),
            'countries' => $this->leadQuery()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
            'sources' => collect(['Website', 'Facebook', 'Google Ads', 'Referral', 'Instagram'])->merge($this->leadQuery()->whereNotNull('source')->distinct()->pluck('source'))->unique(),
            'statuses' => self::STATUSES, 'leadStatuses' => $this->leadStatuses(), 'perPage' => $perPage, 'period' => $request->input('period', self::LIST_PERIOD), // 'status' is deliberately excluded: the stage is chosen with the KPI cards, not in the
            // filter panel, so selecting a card must not spring the panel open.
            // 'search' is a plain string; everything else may now arrive as an array, where
            // `!== 'all'` would be vacuously true and light the button up for an empty selection.
            'filtered' => collect(self::LIST_FILTERS)->reject(fn ($key) => $key === 'status')
                ->contains(fn ($key) => $key === 'search'
                    ? filled($request->input('search'))
                    : $this->filterValues($request, $key) !== []),
            'tab' => in_array($request->input('tab'), ['overview', 'activity', 'comments'], true) ? $request->input('tab') : 'overview',
        ]);

        return $request->header('X-LeadMax-Fragment') === 'leads'
            ? response($view)->header(\ProtoneMedia\Splade\SpladeCore::HEADER_SKIP_MIDDLEWARE, 'true')
            : $view;
    }

    private function fields(Request $request, ?User $lead = null): array
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100',
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9() .-]+$/'],
            'dial_code' => ['nullable', 'string', 'regex:/^\+\d{1,4}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->where('company_id', app('company')->id)->ignore($lead?->id)],
            'country' => 'nullable|string|max:100', 'source' => 'nullable|string|max:150',
            'created_by' => 'nullable|uuid', 'manager_id' => 'nullable|uuid',
            'note' => 'nullable|string|max:10000', 'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);
        if (! empty($data['dial_code']) && ! empty($data['phone']) && ! str_starts_with($data['phone'], '+')) {
            $data['phone'] = $data['dial_code'].$data['phone'];
        }
        unset($data['dial_code']);
        if (! empty($data['created_by'])) {
            abort_unless(User::query()->whereHasRole('affiliate')->whereKey($data['created_by'])->exists(), 422, 'Choose an affiliate from this tenant.');
        }
        if (! is_admin()) {
            $data['manager_id'] = auth()->id();
        } elseif (! empty($data['manager_id'])) {
            abort_unless($this->staff()->whereKey($data['manager_id'])->exists(), 422, 'Choose an owner from this tenant.');
        }
        $data['email'] = $data['email'] ?? $lead?->email ?? Str::uuid().'@leadmax-contact.invalid';

        return $data;
    }

    // ----------------------------------------------------------------- KYC

    /**
     * Approve or reject one uploaded document.
     *
     * Per-document rather than per-client because that is what the reviewer is
     * actually looking at: a passport can be fine while the utility bill is
     * three years old. The client's overall status is recomputed from whatever
     * the documents now say, so the two can never drift apart.
     */
    public function kycDecision(Request $request, string $id, string $document)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'decision' => ['required', Rule::in([UserId::APPROVED, UserId::REJECTED, UserId::PENDING])],
            // Shown to the client when something comes back rejected -- without
            // it they have no idea what to send instead.
            // A rejection needs it (NC-15: the client must know what to send instead).
            'note' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('decision') === UserId::REJECTED), $request->input('decision') === UserId::REJECTED ? 'min:3' : 'nullable'],
        ]);

        $record = UserId::query()->where('user_id', $lead->id)->findOrFail($document);
        $before = strtolower((string) $lead->verification_status);

        // Through the lifecycle, so a decision lands on a submission that is actually
        // open, and deciding the last document decides the attempt.
        try {
            app(KycLifecycle::class)->decideDocument($record, auth()->user(), $data['decision'], $data['note'] ?? null);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $after = strtolower((string) User::query()->whereKey($lead->id)->value('verification_status'));

        $this->record($lead, 'kyc', 'KYC document '.$data['decision'], [
            'document' => $record->typeLabel(), 'note' => $data['note'] ?? null,
        ]);

        // Only on the transition, so approving a second document does not send
        // a second "you are verified" email.
        if ($after !== $before) {
            $this->mailKycOutcome($lead, $after, $data['note'] ?? null);
        }

        return response()->json(['message' => 'Saved', 'status' => $after]);
    }

    /**
     * The Update KYC Status panel (NC-12/15): a step in the attempt's review, never a
     * free status overwrite. Pending Approval can go under review; Under Review can be
     * approved or rejected (with the reason the client sees). Keeping the current state
     * with a note records an internal reviewer note only -- no new attempt, no email.
     */
    public function kycReview(Request $request, string $id, string $submission)
    {
        $lead = $this->lead($id);
        $header = KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->findOrFail($submission);
        $data = $request->validate([
            'target_state' => ['required', Rule::in([$header->state, KycSubmission::UNDER_REVIEW, KycSubmission::APPROVED, KycSubmission::REJECTED])],
            'version' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
            'rejection_reason' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('target_state') === KycSubmission::REJECTED), $request->input('target_state') === KycSubmission::REJECTED ? 'min:3' : 'nullable'],
        ], [
            'rejection_reason.required' => 'Give the client a reason for the rejection.',
        ]);

        $service = app(KycLifecycle::class);
        $actor = auth()->user();
        $notes = filled($data['notes'] ?? null) ? trim($data['notes']) : null;
        $before = strtolower((string) $lead->verification_status);

        try {
            if ($data['target_state'] === $header->state) {
                if (! $notes) {
                    return response()->json(['message' => 'Nothing to update.']);
                }
                $service->addNote($header, $actor, $notes, (int) $data['version']);
            } elseif ($data['target_state'] === KycSubmission::UNDER_REVIEW) {
                $header = $service->startReview($header, $actor, (int) $data['version']);
                if ($notes) {
                    $service->addNote($header, $actor, $notes, $header->version);
                }
            } else {
                $service->decide($header, $actor, $data['target_state'], $data['rejection_reason'] ?? null, $notes, (int) $data['version']);
            }
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $header->refresh();
        $after = strtolower((string) User::query()->whereKey($lead->id)->value('verification_status'));
        $this->record($lead, 'kyc', 'KYC '.$header->label(), ['note' => $notes, 'reason' => $data['rejection_reason'] ?? null]);

        // Once, on an actual decision that changed the client's status.
        if ($header->isFinal() && $after !== $before) {
            $this->mailKycOutcome($lead, $after, $header->public_note);
        }

        return response()->json(['message' => 'KYC updated', 'state' => $header->state]);
    }

    /** One of this lead's KYC requests; another lead's request is a 404 here (NC-23.1). */
    private function kycRequestOf(User $lead, string $submission): KycSubmission
    {
        return KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->findOrFail($submission);
    }

    /**
     * Accept or deny a whole KYC request from the review modal (NC-23.5): one decision for
     * the bundle, checked against the request version and the client's current level, and
     * idempotent by key. The client hears about it once, after the commit.
     */
    public function kycRequestDecision(Request $request, string $id, string $submission)
    {
        $lead = $this->lead($id);
        $header = $this->kycRequestOf($lead, $submission);
        $data = $request->validate([
            'decision' => ['required', Rule::in([KycSubmission::APPROVED, KycSubmission::REJECTED])],
            'version' => ['required', 'integer'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            [$header, $decided] = app(KycLifecycle::class)->decideRequest($header, auth()->user(), $data['decision'],
                $data['rejection_reason'] ?? null, filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                (int) $data['version'], $data['idempotency_key']);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $emailed = false;
        if ($decided) {
            $this->record($lead, 'kyc', 'KYC request '.$header->reference.' '.$header->label(), ['reason' => $header->public_note]);
            // Every decided request is told to the client -- a denied renewal too, though the
            // client's overall status stays approved.
            $emailed = $this->mailKycOutcome($lead, $header->state, $header->public_note);
        }
        // Said plainly in the receipt the modal reopens on (NC-23.5: report delivery accurately).
        session()->flash('lm_kyc_notice', match (true) {
            ! $decided => 'This decision was already recorded. Nothing was sent again.',
            $emailed => 'Decision saved. The client has been emailed.',
            default => 'Decision saved, but the client could not be emailed (no email address, or the mail service refused it). Let them know another way.',
        });

        return response()->json([
            'message' => $header->state === KycSubmission::APPROVED ? 'Request accepted' : 'Request denied',
            'state' => $header->state,
            'label' => $header->label(),
            'emailed' => $emailed,
            'repeated' => ! $decided,
        ]);
    }

    /** Mark one document version as reviewed, or not (NC-23.4). */
    public function kycAcknowledge(Request $request, string $id, string $submission, string $document)
    {
        $lead = $this->lead($id);
        $header = $this->kycRequestOf($lead, $submission);
        $data = $request->validate(['reviewed' => ['required', 'boolean'], 'version' => ['required', 'integer']]);
        $record = UserId::query()->where('user_id', $lead->id)->findOrFail($document);
        $service = app(KycLifecycle::class);

        try {
            $header = $service->acknowledge($header, $record, auth()->user(), (bool) $data['reviewed'], (int) $data['version']);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $blocker = $service->acceptBlocker($header);

        // The bundle's review progress comes from the same projection as every other KYC view (NC-24.3).
        $bundle = \App\Services\LeadMax\LeadDetailTabs::kycBundle($header, $service->documentsOf($header));

        return response()->json(['version' => $header->version, 'reviewed' => (bool) $data['reviewed'], 'can_accept' => $blocker === null, 'blocker' => $blocker, 'bundle' => $bundle['progress']]);
    }

    /** Take over a request another agent is reviewing (NC-23.4: never silently). */
    public function kycTakeOver(Request $request, string $id, string $submission)
    {
        $lead = $this->lead($id);
        $header = $this->kycRequestOf($lead, $submission);
        $version = (int) $request->validate(['version' => ['required', 'integer']])['version'];

        try {
            $header = app(KycLifecycle::class)->takeOver($header, auth()->user(), $version);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }
        $this->record($lead, 'kyc', 'KYC request '.$header->reference.' taken over');

        return response()->json(['message' => 'You are now reviewing this request.', 'version' => $header->version]);
    }

    /**
     * This lead's KYC history as CSV (NC-23.6): the filtered requests across every page in the
     * current sort, or exactly the selected ones. Visible fields only -- no document numbers, the
     * public note, no internal notes, storage paths or files. A selected id that is not one of
     * this lead's requests refuses the whole export rather than returning part of it.
     */
    public function kycExport(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $ids = $request->validate(['ids' => ['nullable', 'array', 'max:500'], 'ids.*' => ['uuid']])['ids'] ?? [];
        $tabs = app(LeadDetailTabs::class);

        if ($ids) {
            $ids = array_values(array_unique($ids));
            $known = KycSubmission::withoutGlobalScopes()->where('user_id', $lead->id)->whereIn('id', $ids)->count();
            abort_if($known !== count($ids), 422, 'Some of the selected KYC requests do not belong to this lead. Nothing was exported.');
            // The selection in the current sort, whatever the filters now hide.
            $rows = $tabs->kycRequestList($lead, new Request($request->only(['kh_sort', 'kh_dir'])))['rows']->whereIn('uuid', $ids)->values();
        } else {
            $rows = $tabs->kycRequestList($lead, $request)['rows'];
        }

        $this->record($lead, 'kyc', 'KYC history exported', ['requests' => $rows->count(), 'scope' => $ids ? 'selected' : 'filtered']);
        $cell = fn ($value) => preg_match('/^[=+@\-\t\r\n]/', (string) $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($rows, $cell) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['KYC Request ID', 'Submission Date (UTC)', 'Document Type', 'Files', 'Proof of Address', 'Country', 'Status', 'Qualifiers', 'Reviewed By', 'Reviewed Date (UTC)', 'Expiry Date', 'Risk Score', 'Notes / Issues']);
            foreach ($rows as $row) {
                fputcsv($out, array_map($cell, [
                    $row['reference'] ?? 'Attempt '.$row['attempt'],
                    $row['submitted_at']?->format('Y-m-d H:i:s') ?? 'Not recorded',
                    $row['primary'],
                    $row['file_count'],
                    $row['proof'],
                    $row['country_code'] ? strtoupper($row['country_code']) : 'Not recorded',
                    $row['state_label'],
                    implode(', ', array_keys(array_filter(['Resubmitted' => $row['resubmitted'], 'Expired' => $row['expired']]))),
                    $row['reviewer'] ?? 'Not reviewed',
                    $row['reviewed_at']?->format('Y-m-d H:i:s') ?? '',
                    $row['expiry']?->format('Y-m-d') ?? 'Not recorded',
                    $row['risk_score'] !== null ? $row['risk_score'] : 'Not assessed',
                    $row['note'] ?? '',
                ]));
            }
            fclose($out);
        }, 'kyc-history-'.($lead->account_id ?: $lead->id).'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Record a document's number, expiry and issuing country while its request is open. */
    public function kycMetadata(Request $request, string $id, string $submission, string $document)
    {
        $lead = $this->lead($id);
        $header = $this->kycRequestOf($lead, $submission);
        $record = UserId::query()->where('user_id', $lead->id)->where('attempt', $header->attempt)->findOrFail($document);
        $data = $request->validate([
            'document_number' => ['nullable', 'string', 'max:50'],
            'document_expiry' => ['nullable', 'date_format:Y-m-d'],
            'issuing_country' => ['nullable', 'string', 'max:60', function ($attribute, $value, $fail) {
                if (filled($value) && ! Geo::find($value)['code']) {
                    $fail('Choose the issuing country from the list.');
                }
            }],
        ]);
        $data['issuing_country'] = filled($data['issuing_country'] ?? null) ? Geo::find($data['issuing_country'])['code'] : null;

        try {
            app(KycLifecycle::class)->recordMetadata($record, auth()->user(), $data);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        return response()->json(['message' => 'Document details saved']);
    }

    /**
     * Ask the client for updated documents after a decided request (NC-23.5). Does not
     * reopen the decision; the client is notified once per request key.
     */
    public function kycRequestUpdate(Request $request, string $id, string $submission)
    {
        $lead = $this->lead($id);
        $header = $this->kycRequestOf($lead, $submission);
        $data = $request->validate([
            'types' => ['required', 'array', 'min:1'],
            'types.*' => [Rule::in(UserId::REQUIRED_TYPES)],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ], ['reason.required' => 'Tell the client what to send and why.']);
        $reason = trim($data['reason']);

        try {
            [$header, $recorded] = app(KycLifecycle::class)->requestUpdate($header, auth()->user(), array_unique($data['types']), $reason, $data['idempotency_key']);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $notified = false;
        if ($recorded) {
            $this->record($lead, 'kyc', 'KYC update requested', ['reference' => $header->reference, 'reason' => $reason]);
            try {
                app(NotificationService::class)->notify($reason, 'kyc_update', 'Please update your KYC documents', $lead, 'warning', ['meta' => [
                    'user_id' => $lead->id, 'reference' => $header->reference, 'types' => array_values(array_unique($data['types'])),
                ]]);
                $notified = true;
            } catch (\Throwable $e) {
                // The request is recorded either way; say plainly that the notice did not go out.
                \Illuminate\Support\Facades\Log::warning('KYC update request notice failed', ['lead' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        $message = $notified ? 'Update requested; the client was notified.' : ($recorded ? 'Update requested, but the client could not be notified. Let them know another way.' : 'This update was already requested. Nothing was sent again.');
        session()->flash('lm_kyc_notice', $message);

        return response()->json(['message' => $message, 'notified' => $notified]);
    }

    /**
     * One step of the Add KYC wizard (NC-13/14): validated, then saved into the lead's open
     * draft. A snapshot of what was declared -- the lead's profile is never changed.
     */
    public function kycDraft(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $step = $request->validate(['step' => ['required', Rule::in(KycWizard::STEPS)]])['step'];
        // The same step rules as the client area's KYC window (App\Support\KycWizard).
        $fields = KycWizard::fields($step, $request->validate(KycWizard::rules($step), KycWizard::messages()));

        try {
            $header = app(KycLifecycle::class)->saveDraft($lead, auth()->user(), $step, $fields, $request->allFiles());
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        // Progress is process progress, never approval: 25% after Personal, 50% after Identity.
        return response()->json(['message' => 'Saved', 'version' => $header->version, 'progress' => $step === 'personal' ? 25 : 50]);
    }

    /**
     * Submit the wizard's draft. KYC submitted by staff is approved at once -- they checked
     * the documents before uploading (user ruling, 9 Oct 2026).
     */
    public function kycSubmit(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $version = (int) $request->validate(['version' => ['required', 'integer']])['version'];
        $before = strtolower((string) $lead->verification_status);

        try {
            $header = app(KycLifecycle::class)->submitFromCrm($lead, auth()->user(), $version);
        } catch (KycConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'error_type' => $e->errorType], 409);
        }

        $after = strtolower((string) User::query()->whereKey($lead->id)->value('verification_status'));
        $this->record($lead, 'kyc', 'KYC submitted and approved by staff', ['attempt' => $header->attempt]);
        // The receipt only says the client was emailed when the email was actually handed over.
        $emailed = $after !== $before && $this->mailKycOutcome($lead, $after, null);

        $idDocument = UserId::query()->where('user_id', $lead->id)->where('attempt', $header->attempt)->where('type', 'id')->value('id');

        return response()->json([
            'message' => 'KYC approved',
            'state' => $header->state,
            'label' => $header->label(),
            'submitted_at' => $header->submitted_at?->format('Y-m-d H:i'),
            'submitted_by' => auth()->user()->name,
            'emailed' => $emailed,
            'document' => $idDocument,
        ]);
    }

    /** Set the client's KYC status directly, with a note, from the Update KYC Status card. */
    public function kycStatus(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(LeadFields::KYC_STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->applyKycStatus($lead, $data['status'], $data['note'] ?? null);

        return response()->json(['message' => 'Saved']);
    }

    /**
     * Set a client's KYC status, and make the documents agree with it.
     *
     * Shared by the Update KYC Status card and the KYC status field on the lead
     * details grid. Those were two different things until now: the card decided
     * the documents too, the field wrote the column on its own. A status set from
     * the field therefore sat on top of documents that were still pending, and
     * the next per-document decision recomputed the status from them and threw
     * the choice away -- which is what "the KYC status does not work" looks like
     * from the other side of the screen.
     */
    private function applyKycStatus(User $lead, string $status, ?string $note = null): void
    {
        $before = strtolower((string) $lead->verification_status);
        $lead->forceFill(['verification_status' => $status])->save();

        // Deciding the client as a whole decides the documents still waiting on
        // someone; leaving them pending would show the client "approved" over a
        // list of pending rows.
        if (in_array($status, ['approved', 'completed', 'rejected'], true)) {
            $decision = $status === 'rejected' ? UserId::REJECTED : UserId::APPROVED;
            UserId::query()->where('user_id', $lead->id)->where('status', UserId::PENDING)
                ->update([
                    'status' => $decision,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'review_note' => $note,
                ]);
        }

        // Still a direct override (user ruling, 9 Oct 2026), but recorded on the attempt
        // and deciding it too, so the attempt and the client's status agree.
        app(KycLifecycle::class)->override($lead, auth()->user(), $status, $note);

        $this->record($lead, 'kyc', 'KYC status set to '.$status, ['note' => $note]);

        if (strtolower($status) !== $before) {
            $this->mailKycOutcome($lead, $status, $note);
        }
    }

    /**
     * Stream a KYC document to a reviewer.
     *
     * Not a link to the object: the bucket denies public reads, which is why
     * every one of these images was broken in the admin UI until now. Opening
     * the bucket would have published every passport on the platform.
     */
    public function kycFile(Request $request, string $id, string $document, string $side = 'front')
    {
        $lead = $this->lead($id);
        $record = UserId::query()->where('user_id', $lead->id)->findOrFail($document);

        return Kyc::stream($record, $side === 'back' ? 'back' : 'front', $request->boolean('download'))
            ?? abort(404, 'That file is no longer in storage.');
    }

    /** Upload a document on the client's behalf, e.g. one that arrived by email. */
    public function kycUpload(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'type' => ['required', Rule::in(['id', 'proof_of_address', 'selfie'])],
            'document' => KycFile::rules(required: true),
        ]);

        try {
            app(KycLifecycle::class)->legacyUpload($lead, $data['type'], ['document' => $request->file('document')], null, auth()->user(), 'crm');
        } catch (KycConflict $e) {
            return back()->withErrors(['document' => $e->getMessage()]);
        }

        $this->record($lead, 'kyc', 'KYC document uploaded by staff', ['type' => $data['type']]);

        return back()->with('success', 'Document uploaded.');
    }

    /**
     * Tell the client what was decided, on the tenant's own mailer.
     *
     * These mailables have existed since the platform was built and were
     * dispatched from nowhere, which is why no lead has ever heard back about a
     * KYC submission. A bare Mail::to() would brand it as the wrong broker --
     * one install serves every tenant.
     */
    /** @return bool whether an email was queued or sent */
    private function mailKycOutcome(User $lead, string $status, ?string $note): bool
    {
        if (blank($lead->email)) {
            return false;
        }

        $payload = ['customerName' => trim($lead->first_name.' '.$lead->last_name), 'note' => $note];

        try {
            $mailable = match (strtolower($status)) {
                'approved', 'completed' => new \App\Mail\KycApprovedMail($payload, $lead->company_id),
                'rejected' => new \App\Mail\KycRejectedMail($payload, $lead->company_id),
                default => null,
            };

            if ($mailable) {
                \Illuminate\Support\Facades\Mail::to($lead->email)->send($mailable);

                return true;
            }
        } catch (\Throwable $e) {
            // A mail failure must not undo a review that has already been saved.
            \Illuminate\Support\Facades\Log::warning('KYC outcome email failed', [
                'lead' => $lead->id, 'status' => $status, 'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /** A subquery for one name field of the user a lead column points at, to order by without a join. */
    private function personName(string $column, string $field)
    {
        return User::withoutGlobalScopes()->from('users as person')->select('person.'.$field)->whereColumn('person.id', 'users.'.$column)->limit(1);
    }

    private function record(User $lead, string $type, string $title, array $info = []): ActivityLog
    {
        return ActivityLog::create(['user_id' => $lead->id, 'type' => $type, 'title' => $title, 'info' => ['actor_id' => auth()->id(), 'actor' => auth()->user()->name, 'ip' => request()->ip(), 'user_agent' => \Illuminate\Support\Str::limit((string) request()->userAgent(), 255, ''), ...ActivityContext::current(), ...$info]]);
    }

    public function store(Request $request)
    {
        $data = $this->fields($request);
        $lead = DB::transaction(function () use ($data) {
            $company = app('company');
            $company->newQuery()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            enforce_plan_limit('leads_max', User::query()->whereHasRole('lead')->count(), $company);
            $lead = User::create([...collect($data)->except('note')->all(), 'status' => $data['status'] ?? 'new', 'password' => bcrypt('ABC123'), 'can_email' => false, 'can_trade' => false, 'approved' => false]);
            $lead->addRole('lead');
            $this->record($lead, 'lead_created', 'Lead created');
            // With the Lead Splitter on, leads added without an owner are routed by its rules.
            $assignedBy = $lead->manager_id ? 'manual' : null;
            if (! $lead->manager_id && app(\App\Services\LeadMax\LeadSplitter::class)->enabled()) {
                app(\App\Services\LeadMax\LeadAssigner::class)->assign($lead, 'splitter', auth()->id());
                $assignedBy = $lead->manager_id ? 'splitter' : null;
            }
            app(LeadCapture::class)->record($lead, 'manual', ['actor_id' => auth()->id(), 'assignment_method' => $assignedBy]);
            if (! empty($data['note'])) {
                Note::create(['user_id' => $lead->id, 'by_id' => auth()->id(), 'note' => $data['note']]);
            }

            return $lead;
        });

        return response()->json(['message' => 'Lead added', 'id' => $lead->id]);
    }

    public function update(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $this->fields($request, $lead);
        DB::transaction(function () use ($lead, $data) {
            $lead->update(collect($data)->except('note')->all());
            $this->record($lead, 'lead_updated', 'Lead updated');
        });

        return response()->json(['message' => 'Lead updated']);
    }

    public function assign(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $data = $request->validate(['manager_id' => 'required|uuid']);
        $owner = $this->staff()->findOrFail($data['manager_id']);
        $this->assignOwner($lead, $owner);

        $request->session()->flash('leadmax_owner_assigned', 'Lead '.($lead->account_id ?: '--').' assigned to '.$owner->name.'.');

        return response()->json(['message' => 'Owner assigned']);
    }

    public function status(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)], 'note' => 'nullable|string|max:500']);
        $this->applyStage($lead, $data['status'], $data['note'] ?? null);

        return response()->json(['message' => 'Status updated']);
    }

    public function leadStatus(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['lead_status' => ['nullable', 'string', Rule::in($this->leadStatuses())], 'note' => 'nullable|string|max:500']);
        $this->applyLeadStatus($lead, $data['lead_status'] ?? null, $data['note'] ?? null);

        return response()->json(['message' => 'Lead status updated']);
    }

    /**
     * Inline edit of one field on the lead details grid (see LeadFields). `phone_extra` / `email_extra` add a second
     * number or address, and with `remove` set they drop the one at that index. Returns the new display text;
     * `reload` asks the page to refresh when other fields depend on the change (owner, stage).
     */
    public function field(Request $request, string $id)
    {
        $lead = $this->lead($id)->load(['affiliate', 'manager']);
        $key = (string) $request->input('field');
        if (in_array($key, ['phone_extra', 'email_extra'], true)) {
            return $this->extraContact($request, $lead, $key === 'phone_extra' ? 'phones' : 'emails');
        }
        $field = LeadFields::keyed($lead, is_admin())->get($key);
        abort_if(! $field || $field['type'] === 'readonly', 422, 'This field cannot be edited here.');

        $options = array_map('strval', array_keys($field['options'] ?? []));
        $rules = match ($field['type']) {
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->where('company_id', app('company')->id)->ignore($lead->id)],
            'tel' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9() .-]+$/'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'datetime' => ['nullable', 'date'],
            'textarea' => ['nullable', 'string', 'max:2000'],
            'select' => ['nullable', 'string', Rule::in($options)],
            default => ['nullable', 'string', 'max:'.(in_array($key, ['first_name', 'last_name'], true) ? 100 : 150)],
        };
        if (($field['required'] ?? false) || in_array($key, ['first_name', 'last_name'], true)) {
            $rules[0] = 'required';
        }
        $value = $request->validate(['value' => $rules])['value'] ?? null;
        $value = is_string($value) ? trim($value) : $value;
        $value = $value === '' ? null : $value;
        $old = $field['value'];

        match ($key) {
            'stage' => $this->applyStage($lead, $value),
            'label' => $this->applyLeadStatus($lead, $value),
            'manager_id' => $value !== $old ? $this->assignOwner($lead, $this->staff()->findOrFail($value)) : null,
            'email' => DB::transaction(fn () => $lead->update(['email' => $value ?? ($old === null ? $lead->email : Str::uuid().'@leadmax-contact.invalid')])),
            'email_verified' => $lead->forceFill(['email_verified_at' => $value === 'yes' ? ($lead->email_verified_at ?? now()) : null])->save(),
            // Not a plain column write: see applyKycStatus. Setting it here has to
            // decide the pending documents too, or the next document review
            // recomputes the status from them and undoes this.
            'verification_status' => $this->applyKycStatus($lead, (string) $value),
            // plan_id is a real column (Account type), so it falls through to the default arm.
            'description', 'sale_status', 'next_date_call' => LeadFields::putExtra($lead, $key, $key === 'next_date_call' && $value ? Carbon::parse($value)->format('Y-m-d H:i') : $value),
            default => $lead->forceFill([$key => $value])->save(),
        };
        if (! in_array($key, ['stage', 'label', 'manager_id'], true) && (string) $old !== (string) $value) {
            $this->record($lead, 'lead_updated', $field['label'].' updated', ['field' => $key, 'from' => $old, 'to' => $value]);
        }
        // A next call date is a reminder too, so it shows on the Calendar and in the Calls tab.
        if ($key === 'next_date_call' && $value && (string) $old !== (string) Carbon::parse($value)->format('Y-m-d\TH:i')) {
            $when = Carbon::parse($value);
            $this->record($lead, 'reminder_created', 'Reminder created', ['subject' => 'Next call: '.$lead->name, 'reminder_date' => $when->toDateString(), 'reminder_time' => $when->format('H:i'), 'priority' => 'medium', 'agent_id' => $lead->manager_id, 'agent' => $lead->manager?->name, 'description' => null]);
        }
        $reload = in_array($key, ['manager_id', 'stage', 'label', 'first_name', 'last_name', 'timezone'], true);
        $fresh = LeadFields::keyed($lead->fresh(['affiliate', 'manager']), is_admin())->get($key);

        return response()->json(['message' => $field['label'].' saved', 'value' => $fresh['value'], 'display' => LeadFields::display($fresh), 'reload' => $reload]);
    }

    private function extraContact(Request $request, User $lead, string $list)
    {
        $items = array_values((array) (LeadFields::extra($lead)[$list] ?? []));
        if ($request->filled('remove')) {
            $index = (int) $request->input('remove');
            abort_unless(isset($items[$index]), 422, 'Nothing to remove.');
            $removed = $items[$index];
            array_splice($items, $index, 1);
            LeadFields::putExtra($lead, $list, $items);
            $this->record($lead, 'lead_updated', ($list === 'phones' ? 'Phone' : 'Email').' removed', ['field' => $list, 'from' => $removed, 'to' => null]);

            return response()->json(['message' => 'Removed', 'reload' => true]);
        }
        $value = trim((string) $request->validate(['value' => $list === 'phones' ? ['required', 'string', 'max:40', 'regex:/^[+0-9() .-]+$/'] : ['required', 'email', 'max:255']])['value']);
        abort_if(count($items) >= LeadFields::MAX_EXTRA, 422, 'A lead can have at most '.LeadFields::MAX_EXTRA.' extra entries here.');
        abort_if(in_array(mb_strtolower($value), array_map('mb_strtolower', [...$items, (string) ($list === 'phones' ? $lead->phone : $lead->email)]), true), 422, 'This lead already has that '.($list === 'phones' ? 'number' : 'address').'.');
        $items[] = $value;
        LeadFields::putExtra($lead, $list, $items);
        $this->record($lead, 'lead_updated', ($list === 'phones' ? 'Phone' : 'Email').' added', ['field' => $list, 'from' => null, 'to' => $value]);

        return response()->json(['message' => ($list === 'phones' ? 'Phone number' : 'Email').' added', 'reload' => true]);
    }

    /** Deletes a comment: its author or an admin; the attachment file goes with it. */
    public function commentDestroy(string $id, string $note)
    {
        $lead = $this->lead($id);
        $comment = $lead->notes()->findOrFail($note);
        abort_unless(is_admin() || (string) $comment->by_id === (string) auth()->id(), 403, 'You can only delete your own comments.');
        DB::transaction(function () use ($lead, $comment) {
            $log = ActivityLog::query()->where('user_id', $lead->id)->where('type', 'comment_added')->where('info->note_id', $comment->id)->first();
            if ($path = $log?->info['attachment'] ?? null) {
                Storage::disk('local')->delete($path);
            }
            $comment->delete();
            $this->record($lead, 'comment_deleted', 'Comment deleted', ['description' => Str::limit((string) $comment->note, 200)]);
        });

        return response()->json(['message' => 'Comment deleted']);
    }

    /**
     * One action on many leads: the ticked rows (ids[]) or every lead matching the list's current
     * filters (all=1 plus the same query string the list uses). The new owner / lead status / stage is sent
     * as `value`, never under a filter's own name (lead_status, manager_id, status), so "all matching" keeps
     * the filters intact. Delete and owner changes are admin only; delete also needs the admin's password and
     * skips leads with financial or trading history.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(self::BULK_ACTIONS)],
            'all' => 'sometimes|boolean',
            'ids' => 'required_unless:all,1|array|max:500', 'ids.*' => 'uuid',
            'value' => ['exclude_if:action,delete', 'required', 'string', match ($request->input('action')) {
                'assign' => 'uuid',
                'lead_status' => Rule::in([...$this->leadStatuses(), self::LEAD_STATUS_NONE]),
                default => Rule::in(self::STATUSES),
            }],
            'password' => 'required_if:action,delete|nullable|string',
        ]);
        abort_if(in_array($data['action'], ['assign', 'delete'], true) && ! is_admin(), 403);
        if ($data['action'] === 'delete') {
            abort_unless(Hash::check((string) $data['password'], auth()->user()->password), 422, 'The confirmation password is incorrect.');
        }

        if ($request->boolean('all')) {
            [$from, $to] = $this->period($request, self::LIST_PERIOD);
            $query = $this->filtered($request, $from, $to);
        } else {
            $query = $this->leadQuery()->whereIn('id', $data['ids']);
        }
        $total = (clone $query)->count();
        abort_if($total === 0, 422, 'No leads match this selection.');
        abort_if($total > self::BULK_MAX, 422, 'That is '.number_format($total).' leads. Narrow the filters to '.number_format(self::BULK_MAX).' or fewer.');

        $value = $data['value'] ?? null;
        $owner = $data['action'] === 'assign' ? $this->staff()->findOrFail($value) : null;
        $leadStatus = $value === self::LEAD_STATUS_NONE ? null : $value;
        $done = 0;
        $skipped = 0;
        foreach ($query->lazyById(200) as $lead) {
            match ($data['action']) {
                'assign' => $this->assignOwner($lead, $owner),
                'lead_status' => $this->applyLeadStatus($lead, $leadStatus),
                'stage' => $this->applyStage($lead, $value),
                'delete' => $this->deletionBlocker($lead) === null ? $this->deleteLead($lead) : $skipped++,
            };
            $done++;
        }
        $done -= $skipped;

        $message = match ($data['action']) {
            'assign' => "{$done} ".str('lead')->plural($done).' assigned to '.$owner->name.'.',
            'lead_status' => "Lead status set to \"".($leadStatus ?? 'Not set')."\" for {$done} ".str('lead')->plural($done).'.',
            'stage' => "Stage set to \"".ucfirst($value)."\" for {$done} ".str('lead')->plural($done).'.',
            'delete' => "{$done} ".str('lead')->plural($done).' deleted.'.($skipped ? " {$skipped} skipped: they have deposits, trades or a funded account." : ''),
        };

        return response()->json(['message' => $message, 'done' => $done, 'skipped' => $skipped]);
    }

    private function assignOwner(User $lead, User $owner): void
    {
        DB::transaction(function () use ($lead, $owner) {
            $lead->update(['manager_id' => $owner->id]);
            app(\App\Services\LeadMax\LeadAssigner::class)->log($lead, ['agent' => $owner->id, 'rule_id' => null, 'method' => 'manual'], 'manual', auth()->id());
            $this->record($lead, 'owner_assigned', 'Owner assigned', ['owner' => $owner->name]);
        });
    }

    private function applyStage(User $lead, string $stage, ?string $note = null): void
    {
        DB::transaction(function () use ($lead, $stage, $note) {
            $old = $lead->status;
            $lead->update(['status' => $stage]);
            if (! empty($note)) {
                Note::create(['user_id' => $lead->id, 'by_id' => auth()->id(), 'note' => $note]);
            }
            $this->record($lead, 'status_changed', 'Status changed', ['from' => $old, 'to' => $stage, 'description' => $note]);
        });
    }

    private function applyLeadStatus(User $lead, ?string $value, ?string $note = null): void
    {
        $value = $value === '' ? null : $value;
        $old = $lead->label ?: null;
        if ($old === $value && empty($note)) {
            return;
        }
        DB::transaction(function () use ($lead, $value, $old, $note) {
            $lead->forceFill(['label' => $value])->save();
            if (! empty($note)) {
                Note::create(['user_id' => $lead->id, 'by_id' => auth()->id(), 'note' => $note]);
            }
            $this->record($lead, 'lead_status_changed', 'Lead status changed', ['from' => $old, 'to' => $value, 'description' => $note]);
        });
    }

    /** Why a lead may not be deleted (financial or trading history), or null when it can be. */
    private function deletionBlocker(User $lead): ?string
    {
        if ($lead->transactions()->exists() || $lead->accounts()->exists() || (float) $lead->balance !== 0.0 || (float) $lead->ftd_amount > 0) {
            return 'This lead has financial history and cannot be deleted.';
        }
        $funded = $lead->tradingAccounts()->get()->contains(fn ($account) => abs((float) $account->balance) + abs((float) $account->deposit) + abs((float) $account->credit) + abs((float) $account->bonus) + abs((float) $account->profit) > 0);
        if ($funded || DB::table('trades')->where('user_id', $lead->id)->exists()) {
            return 'This lead has a funded trading account or trades and cannot be deleted.';
        }

        return null;
    }

    /** Removes an unused lead with its notes and its empty trading accounts (never ones with money or trades, see deletionBlocker). */
    private function deleteLead(User $lead): void
    {
        DB::transaction(function () use ($lead) {
            $lead->tradingAccounts()->get()->each->delete();
            $lead->notes()->delete();
            $lead->delete();
        });
    }

    public function callHistory(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            "call_type" => ["required", Rule::in(["inbound", "outbound", "missed"])],
            "call_status" => ["required", Rule::in(["answered", "unanswered", "voicemail"])],
            "duration" => ["required", "date_format:H:i:s"],
            "called_at" => ["required", "date"],
            "notes" => ["nullable", "string", "max:2000"],
        ]);
        $this->record($lead, "call_history_created", "Call history created", ["call_type" => $data["call_type"], "call_status" => $data["call_status"], "duration" => $data["duration"], "called_at" => $data["called_at"], "description" => $data["notes"] ?? null]);
        return response()->json(["message" => "Call history saved"]);
    }

    public function reminder(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            "subject" => ["required", "string", "max:255"],
            "reminder_date" => ["required", "date_format:Y-m-d"],
            "reminder_time" => ["required", "date_format:H:i"],
            "priority" => ["required", Rule::in(["high", "medium", "low"])],
            // Same three the calendar offers. Optional so an older form, or the
            // API, still books the meeting it always did.
            "event_type" => ["nullable", Rule::in(array_keys(LeadMaxToolsController::EVENT_TYPES))],
            // Who is to make the call. Defaults to whoever owns the lead, but a manager booking
            // on someone else's behalf is the whole point of the calendar.
            "agent_id" => ["nullable", "uuid", Rule::exists("users", "id")->where("company_id", app("company")->id)],
            "notes" => ["nullable", "string", "max:2000"],
        ]);

        $agent = filled($data["agent_id"] ?? null)
            ? $this->staff()->whereKey($data["agent_id"])->first()
            : $lead->manager;

        $this->record($lead, "reminder_created", "Reminder created", [
            "event_type" => $data["event_type"] ?? "meeting",
            "subject" => $data["subject"],
            "reminder_date" => $data["reminder_date"],
            "reminder_time" => $data["reminder_time"],
            "priority" => $data["priority"],
            // Name as well as id: the calendar should still read sensibly if the agent is
            // removed later, the way a deleted manager used to leave rows unreadable.
            "agent_id" => $agent?->id,
            "agent" => $agent?->name,
            "description" => $data["notes"] ?? null,
        ]);

        return response()->json(["message" => "Reminder saved"]);
    }

    public function comment(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['note' => 'required|string|max:10000', 'attachment' => 'nullable|file|max:10240|mimes:pdf,png,jpg,jpeg,txt,csv,docx,xlsx']);
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('leadmax/attachments', 'local') : null;
        try {
            DB::transaction(function () use ($lead, $request, $data, $path) {
                $note = Note::create(['user_id' => $lead->id, 'by_id' => auth()->id(), 'note' => $data['note']]);
                $this->record($lead, 'comment_added', 'Comment added', ['note_id' => $note->id, 'attachment' => $path, 'filename' => $request->file('attachment')?->getClientOriginalName()]);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Comment posted']);
    }

    public function attachment(string $id, string $note)
    {
        $this->lead($id)->notes()->findOrFail($note);
        $log = ActivityLog::query()->where('user_id', $id)->where('type', 'comment_added')->where('info->note_id', $note)->firstOrFail();
        $path = $log->info['attachment'] ?? null;
        abort_unless($path && str_starts_with($path, 'leadmax/attachments/'), 404);

        return Storage::disk('local')->download($path, basename($log->info['filename'] ?? 'attachment'), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $request->validate(["password" => "required|string"]);
        abort_unless(Hash::check($request->input("password"), auth()->user()->password), 422, "The confirmation password is incorrect.");
        $lead = $this->lead($id);
        // Keep financial and trading records intact: only unused lead contacts may be removed.
        abort_if($reason = $this->deletionBlocker($lead), 422, $reason);
        $this->deleteLead($lead);

        return response()->json(['message' => 'Lead deleted']);
    }

    public function export(Request $request)
    {
        $query = $this->exportQuery($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            $this->writeLeadsCsv($query, $out);
            fclose($out);
        }, 'leadmax-leads.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Same export, written to the company's Google Drive instead of the browser.
     *
     * Shares exportQuery() and writeLeadsCsv() with the download above so the two can never
     * drift — including the leading-quote guard that stops a spreadsheet treating a lead's
     * name as a formula, which matters more in Drive, where Sheets will happily evaluate it.
     */
    public function exportToDrive(Request $request, GoogleDriveService $drive)
    {
        $connection = $drive->connectionFor(app('company')->id);

        if (! $connection || ! $connection->isUsable()) {
            return back()->with('warning', 'Connect Google Drive in Settings → Integrations first.');
        }

        // A folder chosen at export time wins; otherwise fall back to the default set in
        // Settings, and failing that Drive's root.
        $chosen = $request->validate([
            'folder_id' => ['nullable', 'string', 'max:255'],
            'folder_name' => ['nullable', 'string', 'max:255'],
        ]);
        $folderId = $chosen['folder_id'] ?: $connection->export_folder_id;
        $folderName = $chosen['folder_name'] ?: $connection->export_folder_name;

        $handle = fopen('php://temp', 'r+');
        $this->writeLeadsCsv($this->exportQuery($request), $handle);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        try {
            $file = $drive->upload(
                companyId: app('company')->id,
                contents: $csv,
                filename: 'leads-'.now()->format('Y-m-d-His').'.csv',
                mimeType: 'text/csv',
                folderId: $folderId,
            );
        } catch (\Throwable $e) {
            return back()->with('warning', $e->getMessage());
        }

        $where = $folderName ? ' in '.$folderName : '';

        return back()->with('success', 'Saved '.$file['name'].' to Google Drive'.$where.'.');
    }

    /** The filtered, optionally id-restricted lead query both export paths run. */
    private function exportQuery(Request $request)
    {
        [$from, $to] = $this->period($request, self::LIST_PERIOD);
        $query = $this->filtered($request, $from, $to)->with(['affiliate', 'manager']);
        $ids = $request->validate(['ids' => 'sometimes|array|max:500', 'ids.*' => 'uuid'])['ids'] ?? [];
        if ($ids) {
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    /** @param  resource  $out */
    private function writeLeadsCsv($query, $out): void
    {
        fputcsv($out, ['Lead ID', 'Name', 'Phone', 'Email', 'Country', 'Affiliate', 'Source', 'Lead Status', 'Stage', 'Reg Date']);
        foreach ($query->lazyById(200) as $lead) {
            $values = [$lead->account_id ?: $lead->id, $lead->name, $lead->phone, $lead->email, $lead->country, $lead->affiliate?->name, $lead->source, $lead->label, $lead->status, $lead->created_at?->format('Y-m-d H:i:s')];
            fputcsv($out, array_map(fn ($value) => preg_match('/^[=+@\-\t\r\n]/', (string) $value) ? "'".$value : $value, $values));
        }
    }

    public function show(Request $request, string $id)
    {
        $lead = $this->lead($id)->load(['affiliate', 'manager']);
        // Reached only when this viewer may open the lead; clears their own "new lead" dot.
        LeadView::record($lead, $request->user());
        $activity = ActivityLog::query()->where('user_id', $lead->id)->latest()->limit(100)->get();
        $notes = $lead->notes()->with('by')->latest()->get();
        $preferred = company_settings('leadmax_preferred_owners', []);
        $members = $this->staff()->with('roles')->orderBy('first_name')->get()->sortBy(fn ($member) => (($position = array_search($member->id, $preferred, true)) !== false ? $position : count($preferred)))->values();
        $tab = in_array($request->input('tab'), self::DETAIL_TABS, true) ? $request->input('tab') : 'comments';
        $dialog = in_array($request->input('dialog'), self::DETAIL_DIALOGS, true) ? $request->input('dialog') : null;
        if (in_array($dialog, ['assign', 'payment', 'refund', 'withdrawal', 'invoice', 'credit-note', 'adjustment'], true) && ! is_admin()) {
            $dialog = null;
        }

        return view('admin.leadmax.lead-details', [
            ...$this->common($request, 'leads'),
            'lead' => $lead,
            'activity' => $activity,
            'notes' => $notes,
            'members' => $members,
            'leadStatuses' => $this->leadStatuses(),
            'tab' => $tab,
            'dialog' => $dialog,
            ...$this->neighbours($lead),
            'transactions' => $lead->transactions()->latest()->limit(100)->get(),
            'transactionCount' => $lead->transactions()->count(),
            // Sum in SQL: Transaction::type has an accessor that relabels the stored type codes.
            'depositTotal' => (float) $lead->transactions()->whereIn('type', LeadMaxFinanceController::CLIENT_DEPOSIT_TYPES)->where('status', 'approved')->sum('amount'),
            'withdrawalTotal' => (float) $lead->transactions()->whereIn('type', LeadMaxFinanceController::CLIENT_WITHDRAWAL_TYPES)->where('status', 'approved')->sum('amount'),
            'tabData' => app(LeadDetailTabs::class)->data($lead, $tab, $request),
            'signalToClose' => $dialog === 'signal-close' ? LeadSignal::query()->where('user_id', $lead->id)->find($request->input('signal')) : null,
            'historyEvent' => $dialog === 'history-event' ? app(LeadDetailTabs::class)->historyEvent($lead, (string) $request->input('kind'), (string) $request->input('event')) : null,
            'kycDocument' => $dialog === 'kyc-document' ? app(LeadDetailTabs::class)->kycDocument($lead, (string) $request->input('document'), (string) $request->input('side', 'front')) : null,
            'kycNotes' => $dialog === 'kyc-notes' ? app(LeadDetailTabs::class)->kycNotes($lead, (string) $request->input('submission')) : null,
            'kycWizard' => $dialog === 'kyc-new' ? app(LeadDetailTabs::class)->kycWizard($lead) : null,
            // NC-23.4: one KYC request with its whole bundle; only this lead's request opens.
            'kycRequest' => $dialog === 'kyc-request' ? app(LeadDetailTabs::class)->kycRequest($lead, (string) $request->input('request'), $request) : null,
            'tickets' => SupportTicket::query()->where('user_id', $lead->id)->with('assignee')->latest()->get(),
            'invoices' => LeadInvoice::query()->where('user_id', $lead->id)->with(['creditNotes', 'invoice'])->latest('issue_date')->latest()->get(),
            'communicationsBlocked' => (bool) data_get($lead->settings, 'communications_blocked', false),
        ]);
    }

    /** Previous/next lead in the list's default order (numeric account ID, newest first). */
    private function neighbours(User $lead): array
    {
        $order = 'CAST(account_id AS DECIMAL(20,0))';
        $current = (clone $this->leadQuery())->whereKey($lead->id)->selectRaw("$order as position")->value('position');
        if ($current === null) {
            return ['previousLeadId' => null, 'nextLeadId' => null];
        }

        return [
            'previousLeadId' => $this->leadQuery()->whereRaw("$order > ?", [$current])->orderByRaw("$order asc")->value('id'),
            'nextLeadId' => $this->leadQuery()->whereRaw("$order < ?", [$current])->orderByRaw("$order desc")->value('id'),
        ];
    }

    public function block(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $action = $request->validate(['action' => ['required', Rule::in(['block', 'unblock', 'block_communications', 'unblock_communications'])]])['action'];
        DB::transaction(function () use ($lead, $action) {
            match ($action) {
                'block' => $lead->forceFill(['banned' => true])->save(),
                'unblock' => $lead->forceFill(['banned' => false])->save(),
                'block_communications' => $lead->forceFill(['can_email' => false, 'settings' => [...($lead->settings ?? []), 'communications_blocked' => true]])->save(),
                'unblock_communications' => $lead->forceFill(['settings' => [...($lead->settings ?? []), 'communications_blocked' => false]])->save(),
            };
            if ($action === 'block') {
                // A blocked client must not keep an existing session or auto-login link.
                $lead->tokens()->delete();
            }
            $this->record($lead, $action, match ($action) {
                'block' => 'User blocked',
                'unblock' => 'User unblocked',
                'block_communications' => 'All communications blocked',
                'unblock_communications' => 'Communications unblocked',
            });
        });

        return response()->json(['message' => 'Saved']);
    }

    public function email(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['subject' => 'required|string|max:200', 'body' => 'required|string|max:10000']);
        $this->assertCanEmail($lead);
        app(NotificationService::class)->sendMail($lead->email, ['title' => $data['subject'], 'message' => nl2br(e($data['body'])), 'user' => $lead]);
        $this->record($lead, 'email_sent', 'Email sent', ['subject' => $data['subject'], 'description' => Str::limit($data['body'], 500)]);

        return response()->json(['message' => 'Email sent']);
    }

    public function message(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['message' => 'required|string|max:2000', 'color' => ['nullable', Rule::in(['info', 'success', 'warning', 'danger'])]]);
        abort_if((bool) data_get($lead->settings, 'communications_blocked', false), 422, 'Communications are blocked for this lead.');
        app(NotificationService::class)->sendSystemAnnouncement($lead, $data['message'], $data['color'] ?? 'info');
        $this->record($lead, 'in_app_message_sent', 'In-app message sent', ['description' => $data['message']]);

        return response()->json(['message' => 'Message sent']);
    }

    /** Add Payment / Issue Refund reuse the central admin deposit flow so balances and totals stay consistent. */
    /** Adjust Balance on the lead page: one signed amount on the Spot wallet (see BalanceAdjustment). */
    public function adjustment(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:'.\App\Services\LeadMax\BalanceAdjustment::AMOUNT_PATTERN],
            'reason' => 'required|string|min:3|max:500',
            'idempotency_key' => 'nullable|string|max:64',
        ], ['amount.regex' => 'Enter a signed amount with at most two decimals, e.g. +25.00 or -25.00.']);
        $transaction = app(\App\Services\LeadMax\BalanceAdjustment::class)->apply($request, $lead, $data['amount'], $data['reason'], null, $data['idempotency_key'] ?? null);

        return response()->json(['message' => 'Balance adjusted to '.format_price($transaction->details['after'] ?? $lead->fresh()->balance).'.', 'transaction_id' => $transaction->id]);
    }

    public function payment(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $data = $request->validate([
            'type' => ['required', Rule::in(['deposit', 'withdraw'])],
            'amount' => 'required|numeric|gt:0|max:100000000',
            'description' => 'nullable|string|max:500',
            'notify' => ['required', Rule::in(['yes', 'no'])],
            // Destination comes from accounts(), the same list the central panel offers: the lead's
            // trading accounts plus the Credit, Bonus and Spot wallets. Older callers sent nothing
            // and always meant the spot wallet.
            'account' => ['nullable', Rule::in(array_keys(accounts($lead)))],
        ]);
        abort_if($data['type'] === 'withdraw' && (float) $data['amount'] > (float) $lead->balance, 422, 'The refund exceeds the lead balance.');
        $payload = Request::create($request->url(), 'POST', [
            'user_id' => $lead->id, 'amount' => $data['amount'], 'type' => $data['type'], 'description' => $data['description'] ?? null,
            'account' => $data['account'] ?? 'spot_wallet', 'show_in_history' => 'yes', 'alert_notify' => $data['notify'], 'notify' => $data['notify'],
            'alert_color' => $data['type'] === 'withdraw' ? 'red' : 'green',
        ]);
        $payload->setUserResolver(fn () => $request->user());
        $payload->setLaravelSession($request->session());
        DB::transaction(fn () => app(DepositController::class)->store($payload));

        return response()->json(['message' => $data['type'] === 'withdraw' ? 'Refund issued' : 'Payment added']);
    }

    /**
     * "Issue Withdrawal": takes money out of one of the lead's trading accounts (or the spot wallet) through
     * the same admin flow as the main CRM's fund/withdraw form (DepositController::store), so the approved
     * 'w' transaction, the balance debit and the activity log are identical. Only free funds can leave:
     * a trading account's withdrawable balance excludes the margin held by open trades.
     */
    public function withdrawal(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $data = $request->validate([
            'amount' => 'required|numeric|gt:0|max:100000000',
            'source' => 'required|string|max:100',
            'note' => 'nullable|string|max:500',
            'notify' => ['nullable', Rule::in(['yes', 'no'])],
        ]);
        $amount = round((float) $data['amount'], 2);

        $result = DB::transaction(function () use ($request, $lead, $data, $amount) {
            if ($data['source'] === 'spot_wallet') {
                $lockedLead = User::query()->whereKey($lead->id)->lockForUpdate()->first();
                abort_if($amount > (float) $lockedLead->balance, 422, 'The withdrawal exceeds the spot wallet balance ('.format_price((float) $lockedLead->balance).').');
                $account = 'spot_wallet';
                $before = (float) $lockedLead->balance;
                $read = fn () => (float) User::query()->whereKey($lead->id)->value('balance');
                $label = 'Spot Wallet';
            } else {
                abort_unless(str_starts_with($data['source'], 'trading_account:'), 422, 'Choose the account to withdraw from.');
                $tradingAccountId = substr($data['source'], strlen('trading_account:'));
                $tradingAccount = $lead->tradingAccounts()->whereKey($tradingAccountId)->lockForUpdate()->first();
                abort_unless($tradingAccount, 422, 'That trading account does not belong to this lead.');
                $available = round((float) $tradingAccount->withdrawableBalance(), 2);
                $componentsOn = company_settings('wallet_components_enabled', 'no') === 'yes';
                if ($componentsOn) {
                    // The admin flow debits the Deposit component; never let it silently fail on a larger amount.
                    $available = min($available, round((float) $tradingAccount->deposit, 2));
                }
                abort_if($amount > $available, 422, 'The withdrawal exceeds the free balance of account #'.$tradingAccount->account_id.' ('.format_price($available).'). Funds used as margin by open trades cannot be withdrawn.');
                $account = 'trading_account:'.$tradingAccount->id.($componentsOn ? ':deposit' : '');
                $before = (float) $tradingAccount->balance;
                $read = fn () => (float) \App\Models\TradingAccount::query()->whereKey($tradingAccount->id)->value('balance');
                $label = 'trading account #'.$tradingAccount->account_id;
            }

            $notify = $data['notify'] ?? 'no';
            $payload = Request::create($request->url(), 'POST', [
                'user_id' => $lead->id, 'amount' => $amount, 'type' => 'withdraw', 'description' => $data['note'] ?? null,
                'account' => $account, 'show_in_history' => 'yes', 'alert_notify' => $notify, 'notify' => $notify, 'alert_color' => 'red',
            ]);
            $payload->setUserResolver(fn () => $request->user());
            $payload->setLaravelSession($request->session());
            app(DepositController::class)->store($payload);

            // The shared flow reports success even when a wallet refuses the debit; make sure money actually moved.
            abort_if(round($before - $read(), 2) < $amount, 422, 'The withdrawal could not be taken from the '.$label.'. Nothing was changed.');
            $this->record($lead, 'withdrawal_issued', 'Withdrawal issued', ['amount' => $amount, 'account' => $label, 'description' => $data['note'] ?? null]);

            return $label;
        });

        return response()->json(['message' => 'Withdrawal of '.format_price($amount).' issued from the '.$result.'.']);
    }

    public function serviceRequest(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);
        DB::transaction(function () use ($lead, $data) {
            $ticket = SupportTicket::create(['user_id' => $lead->id, 'category' => $data['category'], 'subject' => $data['subject'], 'status' => 'open']);
            SupportTicketReply::create(['ticket_id' => $ticket->id, 'author_type' => 'admin', 'author_id' => auth()->id(), 'message' => $data['message']]);
            $this->record($lead, 'service_request_created', 'Service request created', ['ticket_id' => $ticket->id, 'description' => $data['subject']]);
        });

        return response()->json(['message' => 'Service request created']);
    }

    public function invoiceStore(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $data = $request->validate([
            'type' => ['required', Rule::in(['invoice', 'credit_note'])],
            'invoice_id' => 'nullable|uuid',
            'items' => 'required|array|min:1|max:20',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|gt:0|max:100000',
            'items.*.unit_price' => 'required|numeric|gt:0|max:100000000',
            'currency' => ['required', Rule::in(['USD', 'EUR', 'GBP'])],
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:2000',
            'send' => 'nullable|boolean',
        ]);
        $items = collect($data['items'])->map(fn ($item) => ['description' => $item['description'], 'quantity' => (float) $item['quantity'], 'unit_price' => round((float) $item['unit_price'], 2)])->values()->all();
        $amount = round(collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']), 2);
        $isCredit = $data['type'] === 'credit_note';
        $invoice = null;
        if ($isCredit && ! empty($data['invoice_id'])) {
            $invoice = LeadInvoice::query()->where('user_id', $lead->id)->where('type', 'invoice')->where('status', '!=', 'void')->findOrFail($data['invoice_id']);
            abort_if($amount > $invoice->balanceDue(), 422, 'The credit note exceeds the invoice balance of '.number_format($invoice->balanceDue(), 2).'.');
        }
        if ($request->boolean('send')) {
            $this->assertCanEmail($lead);
        }
        $document = DB::transaction(function () use ($lead, $data, $items, $amount, $isCredit, $invoice) {
            $company = app('company');
            $company->newQuery()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $prefix = $isCredit ? 'CN-' : 'INV-';
            $last = LeadInvoice::query()->where('number', 'like', $prefix.'%')->pluck('number')->map(fn ($number) => (int) substr($number, strlen($prefix)))->max() ?? 0;
            $document = LeadInvoice::create([
                'user_id' => $lead->id, 'type' => $data['type'], 'number' => $prefix.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT),
                'invoice_id' => $invoice?->id, 'items' => $items, 'amount' => $amount, 'currency' => $data['currency'],
                'issue_date' => $data['issue_date'], 'due_date' => $isCredit ? null : ($data['due_date'] ?? null),
                'status' => $isCredit ? 'issued' : 'draft', 'notes' => $data['notes'] ?? null, 'created_by' => auth()->id(),
            ]);
            $this->record($lead, $isCredit ? 'credit_note_created' : 'invoice_created', ($isCredit ? 'Credit note ' : 'Invoice ').$document->number.' created', ['invoice_id' => $document->id, 'description' => $document->currency.' '.number_format($amount, 2)]);

            return $document;
        });
        if ($request->boolean('send')) {
            $this->sendInvoice($lead, $document);
        }

        return response()->json(['message' => ($isCredit ? 'Credit note ' : 'Invoice ').$document->number.' created']);
    }

    public function invoiceStatus(Request $request, string $id, string $invoice)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->lead($id);
        $document = LeadInvoice::query()->where('user_id', $lead->id)->findOrFail($invoice);
        $action = $request->validate(['action' => ['required', Rule::in(['send', 'paid', 'void'])]])['action'];
        abort_if($document->status === 'void', 422, 'This document has been voided.');
        abort_if($action === 'paid' && ($document->isCreditNote() || $document->status === 'paid'), 422, 'Only unpaid invoices can be marked as paid.');
        abort_if($action === 'void' && $document->status === 'paid', 422, 'A paid invoice cannot be voided; issue a credit note instead.');
        match ($action) {
            'send' => $this->sendInvoice($lead, $document),
            'paid' => $document->update(['status' => 'paid', 'paid_at' => now()]),
            'void' => $document->update(['status' => 'void']),
        };
        if ($action !== 'send') {
            $this->record($lead, 'invoice_'.$action, $document->number.' marked '.($action === 'paid' ? 'paid' : 'void'), ['invoice_id' => $document->id]);
        }

        return response()->json(['message' => 'Saved']);
    }

    public function invoiceShow(string $id, string $invoice)
    {
        $lead = $this->lead($id);

        $view = view('admin.leadmax.invoice-print', [
            'lead' => $lead,
            'document' => LeadInvoice::query()->where('user_id', $lead->id)->with(['invoice', 'creditNotes'])->findOrFail($invoice),
        ]);

        // A standalone printable document: keep Splade from wrapping it in the admin layout.
        return response($view->render())->header(\ProtoneMedia\Splade\SpladeCore::HEADER_SKIP_MIDDLEWARE, 'true');
    }

    private function assertCanEmail(User $lead): void
    {
        abort_if((bool) data_get($lead->settings, 'communications_blocked', false), 422, 'Communications are blocked for this lead.');
        abort_if(! $lead->email || str_ends_with($lead->email, '@leadmax-contact.invalid'), 422, 'This lead has no email address.');
    }

    private function sendInvoice(User $lead, LeadInvoice $document): void
    {
        $this->assertCanEmail($lead);
        $label = $document->isCreditNote() ? 'Credit note' : 'Invoice';
        $rows = collect($document->items)->map(fn ($item) => '<tr><td>'.e($item['description']).'</td><td style="text-align:right">'.e($item['quantity']).'</td><td style="text-align:right">'.number_format($item['unit_price'], 2).'</td></tr>')->implode('');
        $body = '<p>'.e($label).' <strong>'.e($document->number).'</strong> dated '.$document->issue_date->format('Y-m-d').($document->due_date ? ', due '.$document->due_date->format('Y-m-d') : '').'.</p>'
            .'<table width="100%" cellpadding="6" style="border-collapse:collapse"><tr><th align="left">Description</th><th align="right">Qty</th><th align="right">Unit price</th></tr>'.$rows.'</table>'
            .'<p><strong>Total: '.e($document->currency).' '.number_format((float) $document->amount, 2).'</strong></p>'
            .($document->notes ? '<p>'.nl2br(e($document->notes)).'</p>' : '');
        app(NotificationService::class)->sendMail($lead->email, ['title' => $label.' '.$document->number, 'message' => $body, 'user' => $lead]);
        if (! $document->isCreditNote() && $document->status === 'draft') {
            $document->update(['status' => 'sent', 'sent_at' => now()]);
        } else {
            $document->update(['sent_at' => now()]);
        }
        $this->record($lead, 'invoice_sent', $label.' '.$document->number.' emailed', ['invoice_id' => $document->id]);
    }

    public function ticketUpdate(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'ticket_id' => 'required|uuid',
            'action' => ['required', Rule::in(['escalate', 'assign', 'priority'])],
            'assigned_to' => 'required_if:action,assign|nullable|uuid',
            'priority' => ['required_if:action,priority', 'nullable', Rule::in(SupportTicket::PRIORITIES)],
            'reason' => 'nullable|string|max:2000',
        ]);
        $ticket = SupportTicket::query()->where('user_id', $lead->id)->whereIn('status', ['open', 'in_progress'])->findOrFail($data['ticket_id']);
        $assignee = $data['action'] === 'assign' ? $this->staff()->findOrFail($data['assigned_to']) : null;
        DB::transaction(function () use ($lead, $ticket, $data, $assignee) {
            [$changes, $title] = match ($data['action']) {
                'escalate' => [['priority' => 'urgent', 'escalated_at' => now(), 'status' => 'in_progress'], 'Service request escalated'],
                'assign' => [['assigned_to' => $assignee->id, 'status' => 'in_progress'], 'Service request assigned to '.$assignee->name],
                'priority' => [['priority' => $data['priority']], 'Service request priority set to '.$data['priority']],
            };
            $ticket->update($changes);
            if (! empty($data['reason'])) {
                SupportTicketReply::create(['ticket_id' => $ticket->id, 'author_type' => 'admin', 'author_id' => auth()->id(), 'message' => $data['reason']]);
            }
            $this->record($lead, 'service_request_'.$data['action'], $title, ['ticket_id' => $ticket->id, 'description' => $ticket->subject.(! empty($data['reason']) ? ' — '.$data['reason'] : '')]);
        });

        return response()->json(['message' => 'Service request updated']);
    }

    public function signalStore(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'instrument' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\/._-]+$/'],
            'direction' => ['required', Rule::in(['buy', 'sell'])],
            'entry_price' => 'required|numeric|gt:0',
            'take_profit' => 'nullable|numeric|gt:0',
            'stop_loss' => 'nullable|numeric|gt:0',
            'note' => 'nullable|string|max:1000',
            'notify' => 'nullable|boolean',
        ]);
        $signal = LeadSignal::create([...collect($data)->except('notify')->all(), 'instrument' => strtoupper($data['instrument']), 'user_id' => $lead->id, 'sent_by' => auth()->id(), 'status' => 'active']);
        if ($request->boolean('notify') && ! data_get($lead->settings, 'communications_blocked')) {
            $levels = collect(['TP' => $signal->take_profit, 'SL' => $signal->stop_loss])->filter()->map(fn ($v, $k) => $k.' '.rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.'))->implode(', ');
            app(NotificationService::class)->sendSystemAnnouncement($lead, strtoupper($signal->direction).' '.$signal->instrument.' @ '.rtrim(rtrim(number_format($signal->entry_price, 6, '.', ''), '0'), '.').($levels ? ' ('.$levels.')' : '').($signal->note ? ' — '.$signal->note : ''), 'info', ['signal_id' => $signal->id]);
        }
        $this->record($lead, 'signal_sent', 'Trading signal '.$signal->code().' sent', ['description' => strtoupper($signal->direction).' '.$signal->instrument]);

        return response()->json(['message' => 'Signal '.$signal->code().' sent']);
    }

    public function signalUpdate(Request $request, string $id, string $signal)
    {
        $lead = $this->lead($id);
        $record = LeadSignal::query()->where('user_id', $lead->id)->findOrFail($signal);
        $data = $request->validate(['status' => ['required', Rule::in(['closed', 'expired'])], 'result' => 'nullable|string|max:40']);
        $record->update([...$data, 'closed_at' => now()]);
        $this->record($lead, 'signal_'.$data['status'], 'Trading signal '.$record->code().' '.$data['status'], ['description' => $data['result'] ?? null]);

        return response()->json(['message' => 'Signal updated']);
    }

    public function sessionTerminate(Request $request, string $id, string $token)
    {
        $lead = $this->lead($id);
        $deleted = $lead->tokens()->whereKey($token)->delete();
        abort_unless($deleted, 404);
        $this->record($lead, 'session_terminated', 'Session terminated');

        return response()->json(['message' => 'Session terminated']);
    }

    public function cancelService(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate(['ticket_id' => 'required|uuid']);
        $ticket = SupportTicket::query()->where('user_id', $lead->id)->whereIn('status', ['open', 'in_progress'])->findOrFail($data['ticket_id']);
        DB::transaction(function () use ($lead, $ticket) {
            $ticket->update(['status' => 'closed']);
            $this->record($lead, 'service_request_cancelled', 'Service request cancelled', ['ticket_id' => $ticket->id, 'description' => $ticket->subject]);
        });

        return response()->json(['message' => 'Service request cancelled']);
    }

    public function changePassword(Request $request, string $id)
    {
        $lead = $this->lead($id);
        $data = $request->validate([
            'password'              => 'required|string|min:6|max:64',
            'password_confirmation' => 'required|same:password',
        ]);
        $lead->update(['password' => Hash::make($data['password'])]);
        $this->record($lead, 'password_change', 'Password changed by admin');

        return response()->json(['success' => true]);
    }

    public function sendCredentials(Request $request, string $id)
    {
        $lead = $this->lead($id);

        // Reset password to ABC123 so the emailed credentials are accurate
        $plainPassword = 'ABC123';
        $lead->update(['password' => Hash::make($plainPassword)]);

        $frontendUrl = company_settings('front_end_url') ?? app('company')->url;
        $token = $lead->createToken($lead->email, ['*'], now()->addHours(24))->plainTextToken;

        $emailService = app(\App\Services\EmailService::class);
        $emailService->sendCredentialsEmail($lead, $plainPassword, 'en', $token);

        $this->record($lead, 'credentials_sent', 'Credentials email sent by admin');

        return response()->json(['success' => true]);
    }

    public function copyLink(string $id)
    {
        $lead = $this->lead($id);

        $frontendUrl = company_settings('front_end_url') ?? app('company')->url;
        $token = $lead->createToken($lead->email, ['*'], now()->addHours(24))->plainTextToken;
        $url = "{$frontendUrl}/auto-login?token={$token}";

        return response()->json(['url' => $url]);
    }

    /**
     * "Login as Client": sign straight into the lead's first trading account on the tenant's
     * webtrader (the trade room), through the same single-use launch token the client area uses.
     * The session gets the same abilities as the client-area login (restricted-manager scope,
     * manager attribution). Every lead must have a trading account (the client area is never
     * used), so one is created on the spot when the lead has none, with or without the module.
     */
    public function webtraderLogin(string $id)
    {
        $lead = $this->lead($id);
        $restricted = \App\Services\RestrictedManagerAccess::active();
        if ($restricted) {
            \App\Services\RestrictedManagerAccess::customer($lead);
        }
        $account = app(\App\Services\LeadMax\LeadTradingAccounts::class)->ensureFor($lead);
        $webtraderUrl = company_webtrader_url(app('company'));
        if (! $webtraderUrl) {
            return back()->with('error', 'WebTrader is not set up for this company.');
        }

        $abilities = $restricted ? ['restricted-manager-view'] : ['*'];
        if (auth()->user()->hasRole('manager') && ! is_admin()) {
            $abilities[] = 'manager-actor:'.auth()->id();
        }
        $abilities[] = 'staff-launch';
        [, $rawToken] = \App\Models\TradingAccountLaunchToken::issueFor($account, 45, $abilities);
        $this->record($lead, 'webtrader_login', 'WebTrader login to account '.$account->account_id.' by '.auth()->user()->email);

        return redirect()->away(rtrim($webtraderUrl, '/').'/launch?token='.$rawToken);
    }
}
