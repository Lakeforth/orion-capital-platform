<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * LeadMax Assets, Markups and Calendar (no Figma frames; styled after Finances).
 * Assets is read-only: the assets table is shared by every tenant. Markups edits this company's
 * per-category spread and leverage, the same company settings keys the Trade settings page uses.
 * Calendar shows the reminders agents set on leads (ActivityLog "reminder_created").
 */
class LeadMaxToolsController extends LeadMaxController
{
    /** Settings key prefix => [label, assets.type]. */
    public const CATEGORIES = [
        'forex' => ['Forex', 'forex'],
        'cryptos' => ['Crypto', 'crypto'],
        'stocks' => ['Stocks', 'stocks'],
        'indices' => ['Indices', 'indices'],
        'commodities' => ['Commodities', 'commodities'],
        'metals' => ['Metals', 'metals'],
    ];

    /** Same defaults as Asset::getLeverageAttribute(). */
    public const DEFAULT_LEVERAGE = ['forex' => 10, 'cryptos' => 10, 'stocks' => 20, 'indices' => 20, 'commodities' => 10, 'metals' => 10];

    /** A quote older than this is shown as stale. */
    public const STALE_SECONDS = 900;

    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    /* ---------------------------------------------------------------- Assets */

    /**
     * Columns the Assets table may be ordered by.
     *
     * Bid and Ask are deliberately absent: both are computed per customer from the spread and
     * the tenant's multiplier (see assetRow), so there is no column to order on. Leverage is
     * resolved from the asset's type, so ordering by Category orders by leverage too.
     */
    public const ASSET_SORTS = [
        'instrument' => 'sym',
        'category' => 'type',
        'change' => 'change_percent',
        'volume' => 'volume',
        'updated' => 'price_updated_at',
    ];

    public function assets(Request $request)
    {
        $types = collect(self::CATEGORIES)->mapWithKeys(fn ($c, $key) => [$c[1] => $c[0]])->all();
        $type = array_key_exists($request->input('category'), $types) ? $request->input('category') : null;
        $search = trim($request->string('search')->toString());

        $sort = array_key_exists((string) $request->input('sort'), self::ASSET_SORTS) ? (string) $request->input('sort') : null;
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $query = Asset::query()->where('is_active', true)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('sym', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('sym_key', 'like', "%{$search}%")));

        $counts = Asset::query()->where('is_active', true)->selectRaw('type, COUNT(*) as n')->groupBy('type')->pluck('n', 'type');
        $assets = $query
            ->when($sort, fn ($q) => $q->orderBy(self::ASSET_SORTS[$sort], $direction))
            // Type then symbol stays the tie-break, and is the whole order when nothing is sorted.
            ->orderBy('type')->orderBy('sym')
            ->paginate(25)->withQueryString();
        // A customer with no spread multiplier of their own: the tenant's standard bid/ask.
        $probe = new User;
        $rows = $assets->getCollection()->map(fn (Asset $asset) => $this->assetRow($asset, $probe));

        $data = [
            ...$this->common($request, 'assets'),
            'assets' => $assets, 'rows' => $rows, 'types' => $types, 'type' => $type, 'search' => $search,
            'counts' => $counts, 'leverage' => $this->leverages(),
            'sort' => $sort, 'direction' => $direction,
            'stale' => $rows->where('state', '!=', 'live')->count(),
        ];

        // The page asks for just the table when swapping in place; Splade must not wrap it.
        return $request->header('X-LeadMax-Fragment') === 'assets'
            ? response(view('admin.leadmax.partials.assets-content', $data))
                ->header(\ProtoneMedia\Splade\SpladeCore::HEADER_SKIP_MIDDLEWARE, 'true')
            : view('admin.leadmax.assets', $data);
    }

    /**
     * Switch one instrument on or off for this company only.
     *
     * Never touches assets.is_active: that column is shared by every broker on this install,
     * so flipping it here would take the symbol away from all of them.
     */
    public function toggleAsset(Request $request, string $id)
    {
        $asset = Asset::query()->findOrFail($id);
        $enabled = \App\Support\LeadMax\CompanyAssets::toggle($asset->id);

        \ProtoneMedia\Splade\Facades\Toast::title(
            ($asset->sym_key ?: $asset->sym).($enabled ? ' is on for your clients' : ' is off for your clients')
        );

        return redirect()->back();
    }

    private function assetRow(Asset $asset, User $probe): array
    {
        $price = fn (callable $fn) => rescue(fn () => $asset->rate === null ? null : (float) $fn(), null, false);
        $age = $asset->price_age_seconds;

        return [
            'asset' => $asset,
            'bid' => $price(fn () => $asset->executionSellPriceFor($probe)),
            'ask' => $price(fn () => $asset->executionBuyPriceFor($probe)),
            'precision' => rescue(fn () => $asset->displayPrecision(), 5, false),
            'change' => (float) $asset->change_percent,
            'updated' => $asset->price_updated_at,
            'state' => ! $asset->is_provider_active ? 'off' : ($age === null || $age > self::STALE_SECONDS ? 'stale' : 'live'),
            'enabled' => \App\Support\LeadMax\CompanyAssets::isEnabled($asset->id),
        ];
    }

    /** Leverage per assets.type for this company, as Asset::getLeverageAttribute() resolves it. */
    private function leverages(): array
    {
        $byCategory = company_settings('leverage_system', 'plan_based') === 'asset_category_based';

        return collect(self::CATEGORIES)->mapWithKeys(fn ($c, $key) => [$c[1] => $byCategory ? company_settings($key.'_leverage', self::DEFAULT_LEVERAGE[$key]) : 10])->all();
    }

    /* ---------------------------------------------------------------- Markups */

    public function markups(Request $request)
    {
        $values = collect(self::CATEGORIES)->mapWithKeys(fn ($c, $key) => [$key => [
            'leverage' => company_settings($key.'_leverage', self::DEFAULT_LEVERAGE[$key]),
            'buy' => company_settings($key.'_buy_spread', 0),
            'sell' => company_settings($key.'_sell_spread', 0),
        ]])->all();

        return view('admin.leadmax.markups', [
            ...$this->common($request, 'markups'),
            'values' => $values,
            'leverageSystem' => company_settings('leverage_system', 'plan_based'),
            'leverageSystems' => leverage_system(),
            'twoSided' => company_settings('spread_pricing_model', 'legacy_one_sided') === 'two_sided_percentage',
            'canEdit' => is_admin(),
            'history' => ActivityLog::query()->where('type', 'leadmax_markups')->latest()->limit(5)->get(),
        ]);
    }

    public function saveMarkups(Request $request)
    {
        abort_unless(is_admin(), 403);
        $rules = ['leverage_system' => ['required', Rule::in(array_keys(leverage_system()))]];
        foreach (array_keys(self::CATEGORIES) as $key) {
            $rules[$key.'_leverage'] = ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1', 'max:1000'];
            $rules[$key.'_buy_spread'] = ['required', 'numeric', 'min:0', 'max:2'];
            $rules[$key.'_sell_spread'] = ['required', 'numeric', 'min:0', 'max:2'];
        }
        $data = $request->validate($rules);

        $changes = [];
        DB::transaction(function () use ($data, &$changes) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settingsArray();
            foreach ($data as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $value = (string) $value;
                if ((string) ($settings[$key] ?? '') !== $value) {
                    $changes[$key] = ['from' => $settings[$key] ?? null, 'to' => $value];
                }
                $settings[$key] = $value;
            }
            $company->forceFill(['settings' => $settings])->save();
            Company::clearCache($company->fresh());
            app()->instance('company', $company->fresh());
        });

        if ($changes) {
            log_activity('LeadMax markups updated', ['actor' => auth()->user()->name, 'changes' => $changes], auth()->id(), 'leadmax_markups');
        }

        return redirect()->route('admin.leadmax.markups')->with('success', $changes ? 'Markups saved. New prices apply to new trades.' : 'No changes to save.');
    }

    /* ---------------------------------------------------------------- Calendar */

    /**
     * What a calendar entry can be.
     *
     * Entries predate this and carry no type, so they read as meetings -- which is what every
     * one of them was: a call booked on a lead.
     */
    public const EVENT_TYPES = ['meeting' => 'Meetings', 'campaign' => 'Campaigns', 'task' => 'Tasks'];

    /** The calendar's view modes, as the toggle offers them. */
    public const CALENDAR_VIEWS = ['month' => 'Month', 'week' => 'Week', 'day' => 'Day', 'list' => 'List'];

    public function calendar(Request $request)
    {
        $today = $this->reportingNow()->startOfDay();

        // The day the right-hand rail and the day/week views are centred on. Defaults to today.
        $selected = rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->input('date')), null, false);
        $focus = $selected ?? $today->copy();

        // A chosen day pulls the month to it, so picking 2 November does not leave October open.
        $month = rescue(fn () => Carbon::createFromFormat('!Y-m', (string) $request->input('month')), null, false)
            ?? ($selected ? $selected->copy()->startOfMonth() : $today->copy()->startOfMonth());

        $view = array_key_exists($request->input('view'), self::CALENDAR_VIEWS) ? $request->input('view') : 'month';

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        // The window the listed entries are drawn from: the month grid, or the focused week/day.
        [$from, $to] = match ($view) {
            'week' => [$focus->copy()->startOfWeek(Carbon::SUNDAY), $focus->copy()->endOfWeek(Carbon::SATURDAY)],
            'day' => [$focus->copy()->startOfDay(), $focus->copy()->endOfDay()],
            'list' => [$today->copy(), $today->copy()->addMonths(3)->endOfDay()],
            default => [$gridStart, $gridEnd],
        };

        $rows = $this->reminders()
            ->where('info->reminder_date', '>=', $from->toDateString())
            ->where('info->reminder_date', '<=', $to->toDateString())
            ->get()->map(fn ($log) => $this->reminderRow($log))->filter()->sortBy('at')->values();

        // The month's own entries, for the counters, independent of which view is open.
        $monthRows = $view === 'month' ? $rows : $this->reminders()
            ->where('info->reminder_date', '>=', $month->copy()->startOfMonth()->toDateString())
            ->where('info->reminder_date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->get()->map(fn ($log) => $this->reminderRow($log))->filter()->values();
        $monthRows = $monthRows->filter(fn ($r) => $r['at']->isSameMonth($month));

        // The counters read the month without the type filter, so selecting one card does not
        // blank the others.
        $untyped = $this->reminders(withType: false)
            ->where('info->reminder_date', '>=', $month->copy()->startOfMonth()->toDateString())
            ->where('info->reminder_date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->get()->map(fn ($log) => $this->reminderRow($log))->filter()
            ->filter(fn ($r) => $r['at']->isSameMonth($month));
        $monthTotals = ['all' => $untyped->count()]
            + collect(self::EVENT_TYPES)->map(fn ($label, $key) => $untyped->where('type', $key)->count())->all();

        $weeks = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $weeks[intdiv($gridStart->diffInDays($day), 7)][] = $day->copy();
        }

        $upcoming = $this->reminders()
            ->where('info->reminder_date', '>=', $today->toDateString())
            ->get()->map(fn ($log) => $this->reminderRow($log))->filter()
            ->filter(fn ($r) => $r['at']->gte($this->reportingNow()->subHour()))->sortBy('at')->values();

        $data = [
            ...$this->common($request, 'calendar'),
            'month' => $month, 'weeks' => $weeks, 'today' => $today,
            'byDay' => $rows->groupBy(fn ($r) => $r['at']->toDateString()),
            'rows' => $rows,
            'view' => $view,
            'views' => self::CALENDAR_VIEWS,
            'focus' => $focus,
            'selectedDate' => $selected,
            // The strip of days under "Find meetings by date": the focused day's working week.
            'strip' => collect(range(0, 4))->map(fn ($i) => $focus->copy()->startOfWeek(Carbon::MONDAY)->addDays($i)),
            // What the rail shows: the focused day's entries, or the next ones if it is empty.
            'dayRows' => $rows->filter(fn ($r) => $r['at']->isSameDay($focus))->values(),
            'upcoming' => $upcoming->take(12),
            'monthCount' => $monthRows->count(),
            // Counted off an unfiltered read of the month, so the cards keep showing the whole
            // picture while one of them is selected -- otherwise choosing Tasks would zero the
            // other three and there would be no way to read your way back.
            'stats' => $monthTotals,
            'eventTypes' => self::EVENT_TYPES,
            'selectedType' => array_key_exists(request()->input('type'), self::EVENT_TYPES) ? request()->input('type') : null,
            'leads' => $this->calendarLeads(),
            'priorities' => self::PRIORITIES,
            'mine' => $request->boolean('mine'),
            'agents' => is_admin()
                ? User::query()->whereHasRole(['admin', 'tenant_admin', 'manager', 'agent'])->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                : collect(),
            'selectedAgent' => request()->input('agent'),
        ];

        // The page asks for just the calendar when swapping in place; Splade must not wrap it.
        return $request->header('X-LeadMax-Fragment') === 'calendar'
            ? response(view('admin.leadmax.partials.calendar-content', $data))
                ->header(\ProtoneMedia\Splade\SpladeCore::HEADER_SKIP_MIDDLEWARE, 'true')
            : view('admin.leadmax.calendar', $data);
    }

    /** Leads this user may book an event against, for the Add Event picker. */
    private function calendarLeads()
    {
        return User::query()->whereHasRole('lead')
            ->when(! is_admin(), fn ($q) => $q->where('manager_id', auth()->id()))
            ->orderBy('first_name')->limit(500)->get(['id', 'first_name', 'last_name', 'account_id']);
    }

    /**
     * Add an event from the calendar.
     *
     * The lead is optional: a campaign or an internal task need not be about anyone in
     * particular. Without one the entry hangs off the agent it is assigned to, which is already
     * inside what reminders() lets each person see.
     */
    /**
     * Leads matching what has been typed into an event's "with" box.
     *
     * Deliberately runs the same filterSearch the leads list uses rather than a
     * query of its own: that one already knows how to match a full name across
     * two columns and a phone typed the way it is displayed, and a picker that
     * found a different set of people from the list would be its own bug.
     *
     * Scoped the way the old lead dropdown was -- an agent sees their own leads,
     * an admin sees the company's.
     */
    public function calendarPeople(Request $request)
    {
        $term = trim($request->string('q')->toString());

        // Two characters before asking the database: one letter matches most of
        // the book and the list would be noise.
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $query = User::query()->whereHasRole('lead')
            ->when(! is_admin(), fn ($q) => $q->where('manager_id', auth()->id()));

        $this->filterSearch($query, $request->merge(['search' => $term]));

        $leads = $query->orderBy('first_name')->limit(12)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'account_id']);

        return response()->json([
            'data' => $leads->map(fn (User $lead) => [
                'id' => $lead->id,
                'name' => trim($lead->first_name.' '.$lead->last_name) ?: 'Unnamed lead',
                // What was searched on, so the row shows why it matched.
                'detail' => collect([$lead->account_id ? '#'.$lead->account_id : null, $lead->email, $lead->phone])
                    ->filter()->implode(' · '),
            ])->values(),
        ]);
    }

    public function storeCalendarEvent(Request $request)
    {
        $data = $request->validate([
            'event_type' => ['required', Rule::in(array_keys(self::EVENT_TYPES))],
            'subject' => ['required', 'string', 'max:255'],
            'reminder_date' => ['required', 'date_format:Y-m-d'],
            'reminder_time' => ['required', 'date_format:H:i'],
            'priority' => ['required', Rule::in(array_keys(self::PRIORITIES))],
            'agent_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('company_id', app('company')->id)],
            'lead_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('company_id', app('company')->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $agent = User::query()->whereHasRole(['admin', 'tenant_admin', 'manager', 'agent'])->whereKey($data['agent_id'])->firstOrFail();
        $lead = filled($data['lead_id'] ?? null) ? $this->calendarLeads()->firstWhere('id', $data['lead_id']) : null;

        ActivityLog::create([
            'user_id' => $lead?->id ?? $agent->id,
            'type' => 'reminder_created',
            'title' => 'Reminder created',
            'info' => [
                'actor' => auth()->user()?->name,
                'event_type' => $data['event_type'],
                'standalone' => $lead === null,
                'subject' => $data['subject'],
                'reminder_date' => $data['reminder_date'],
                'reminder_time' => $data['reminder_time'],
                'priority' => $data['priority'],
                'agent_id' => $agent->id,
                'agent' => $agent->name,
                'description' => $data['notes'] ?? null,
            ],
        ]);

        return back()->with('leadmax_calendar_saved', self::EVENT_TYPES[$data['event_type']].' event added for '.$agent->name.'.');
    }

    /**
     * Reminder logs this user may see.
     *
     * Admins see everything. An agent sees the leads they own, plus anything booked against
     * them on a lead they do not own -- a call assigned to you has to reach your calendar, or
     * assigning it achieved nothing.
     */
    private function reminders(bool $withType = true)
    {
        $people = User::query()->select('id')->when(! is_admin(), fn ($q) => $q->where('manager_id', auth()->id()));

        $query = ActivityLog::query()->with(['user:id,first_name,last_name,account_id,manager_id', 'user.manager:id,first_name,last_name'])
            ->where('type', 'reminder_created');

        $query->when(
            is_admin(),
            fn ($q) => $q->whereIn('user_id', $people),
            fn ($q) => $q->where(fn ($inner) => $inner->whereIn('user_id', $people)->orWhere('info->agent_id', auth()->id()))
        );

        // Admins can read one agent's calendar on its own.
        if (is_admin() && filled(request()->input('agent'))) {
            $agent = request()->string('agent')->toString();
            $query->where(fn ($q) => $q->where('info->agent_id', $agent)->orWhere(fn ($legacy) => $legacy->whereNull('info->agent_id')->whereIn('user_id', User::query()->select('id')->where('manager_id', $agent))));
        }

        // The summary cards narrow the calendar by type. 'meeting' has to include the entries
        // written before types existed, which carry no event_type at all.
        $type = request()->input('type');
        if ($withType && array_key_exists($type, self::EVENT_TYPES)) {
            $query->when(
                $type === 'meeting',
                fn ($q) => $q->where(fn ($i) => $i->where('info->event_type', 'meeting')->orWhereNull('info->event_type')),
                fn ($q) => $q->where('info->event_type', $type)
            );
        }

        // "Assigned to me": the calls this user is down to make, whoever owns the lead.
        if (request()->boolean('mine')) {
            $query->where('info->agent_id', auth()->id());
        }

        return $query;
    }

    private function reminderRow(ActivityLog $log): ?array
    {
        $info = $log->info ?? [];
        $at = rescue(fn () => Carbon::createFromFormat('Y-m-d H:i', ($info['reminder_date'] ?? '').' '.($info['reminder_time'] ?? '00:00')), null, false);
        if (! $at || ! $log->user) {
            return null;
        }

        return [
            'at' => $at,
            'subject' => $info['subject'] ?? 'Reminder',
            'priority' => array_key_exists($info['priority'] ?? '', self::PRIORITIES) ? $info['priority'] : 'medium',
            'notes' => $info['description'] ?? null,
            'by' => $info['actor'] ?? null,
            // Who is booked to make the call. Older rows predate this and fall back to whoever
            // owns the lead now.
            'type' => array_key_exists($info['event_type'] ?? '', self::EVENT_TYPES) ? $info['event_type'] : 'meeting',
            // An event booked without a lead hangs off the agent instead, so the row must not
            // offer a link to a lead page that does not exist.
            'standalone' => (bool) ($info['standalone'] ?? false),
            'agent_id' => $info['agent_id'] ?? $log->user?->manager_id,
            'agent' => $info['agent'] ?? $log->user?->manager?->name,
            'lead' => $log->user,
        ];
    }
}
