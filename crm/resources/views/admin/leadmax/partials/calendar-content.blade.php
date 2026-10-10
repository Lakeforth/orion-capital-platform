{{-- The calendar's own markup, returned on its own for the in-place swap: switching between
     the summary cards, the views or the months should not reload the shell. Rendered inside
     calendar.blade.php on a full page load, and alone for an X-LeadMax-Fragment: calendar read. --}}
@php
    $base = fn (array $extra = []) => route('admin.leadmax.calendar', array_filter([
        'month' => $month->format('Y-m'),
        'view' => $view,
        'date' => $selectedDate?->toDateString(),
        'agent' => $selectedAgent,
        'mine' => $mine ? 1 : null,
        'type' => $selectedType,
        ...$extra,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp
<div class="lm-tools lm-calendar" data-lm-calendar="{{ route('admin.leadmax.calendar') }}">
    <header class="lm-tools-top">
        <div><h1>Calendar</h1><p>View and manage important events, meetings, and tasks.</p></div>
        <div class="lm-head-side">
        <nav class="lm-tools-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Calendar</b></nav>
        <div class="lm-cal-actions">
            <button type="button" class="lm-cal-add" data-lm-cal-open>
                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Add Event
            </button>
            {{-- Shows and hides the date picker and chips below. --}}
            <button type="button" class="lm-tools-btn lm-cal-filters" data-lm-cal-filters aria-expanded="true" aria-controls="lm-cal-find">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M2 4h12M4.5 8h7M6.5 12h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                Filters
            </button>
        </div>
        </div>
    </header>

    @if(session('leadmax_calendar_saved'))
        <p class="lm-cal-saved" role="status">{{ session('leadmax_calendar_saved') }}</p>
    @endif

    {{-- Counters. Each is a real count of booked calls, not a static figure. --}}
    {{-- The counters are the calendar's type filter: each one narrows the grid to its own kind,
         and Total Events clears back to everything. Counts are of the whole month whichever is
         selected, so the row always reads as a summary rather than collapsing to the choice. --}}
    <section class="lm-cal-stats" aria-label="Event types">
        @php
            $cards = [
                ['key' => null, 'label' => 'Total Events', 'count' => $stats['all'], 'tone' => 'blue'],
                ['key' => 'meeting', 'label' => 'Meetings', 'count' => $stats['meeting'], 'tone' => 'teal'],
                ['key' => 'campaign', 'label' => 'Campaigns', 'count' => $stats['campaign'], 'tone' => 'violet'],
                ['key' => 'task', 'label' => 'Tasks', 'count' => $stats['task'], 'tone' => 'amber'],
            ];
        @endphp
        @foreach($cards as $card)
            <a class="lm-cal-stat is-{{ $card['tone'] }} {{ $selectedType === $card['key'] ? 'is-on' : '' }}"
               href="{{ $base(['type' => $card['key']]) }}"
               @if($selectedType === $card['key']) aria-current="true" @endif>
                <span class="lm-cal-stat-mark" aria-hidden="true">
                    @switch($card['key'])
                        @case('meeting')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.7"/><path d="M16.5 11a2.5 2.5 0 1 0 0-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M3 19c0-2.8 2.7-4.5 6-4.5s6 1.7 6 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M17 14.6c2.4.5 4 1.9 4 4.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            @break
                        @case('campaign')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 10v4a1 1 0 0 0 1 1h2l6 4V5L7 9H5a1 1 0 0 0-1 1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M17 9.5a3.5 3.5 0 0 1 0 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M19.5 7a7 7 0 0 1 0 10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            @break
                        @case('task')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 3h8l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 3v4h4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.5 13h7M8.5 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            @break
                        @default
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="16" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 10h17M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m9 15.5 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endswitch
                </span>
                <span class="lm-cal-stat-body">
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ number_format($card['count']) }}</strong>
                    <span>This month</span>
                </span>
            </a>
        @endforeach
    </section>

    {{-- Day picker. The strip is the focused day's working week; the chips say what is narrowing
         the list and give a one-click way out of each. --}}
    <section class="lm-tools-card lm-cal-find" id="lm-cal-find" data-lm-cal-find>
        <header class="lm-cal-find-head">
            <div>
                <h2>Find meetings by date</h2>
                <p>Select a day below or choose any date in the calendar</p>
            </div>
            <a class="lm-tools-btn" href="{{ $base(['date' => $today->toDateString(), 'month' => $today->format('Y-m')]) }}">Today</a>
        </header>
        <div class="lm-cal-strip">
            @foreach($strip as $day)
                <a class="lm-cal-strip-day {{ $day->isSameDay($focus) ? 'is-on' : '' }} {{ $day->isSameDay($today) ? 'is-today' : '' }}"
                   href="{{ $base(['date' => $day->toDateString(), 'month' => $day->format('Y-m')]) }}">
                    <small>{{ strtoupper($day->format('D')) }}</small>
                    <b>{{ $day->day }}</b>
                </a>
            @endforeach
        </div>
        <div class="lm-cal-chips">
            <a class="lm-cal-chip {{ $mine ? 'is-on' : '' }}" href="{{ $base(['mine' => $mine ? null : 1]) }}">Assigned to me</a>
            @if($selectedDate)
                <span class="lm-cal-chip is-static">Date: {{ $selectedDate->format('M j, Y') }}</span>
                <a class="lm-cal-chip" href="{{ $base(['date' => null]) }}">Clear date</a>
            @endif
            @if($agents->isNotEmpty())
                <form class="lm-cal-agent" method="GET" aria-label="Filter by agent" data-lm-cal-filter-form>
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <input type="hidden" name="view" value="{{ $view }}">
                    @if($selectedDate)<input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">@endif
                    @if($mine)<input type="hidden" name="mine" value="1">@endif
                    <label class="sr-only" for="lm-cal-agent">Agent</label>
                    <select id="lm-cal-agent" name="agent" onchange="this.form.submit()">
                        <option value="">All agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) $selectedAgent === (string) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="lm-tools-btn">Show</button></noscript>
                </form>
            @endif
        </div>
    </section>

    <div class="lm-cal-layout">
        <section class="lm-tools-card lm-cal-main">
            <header class="lm-cal-head">
                <nav class="lm-cal-nav" aria-label="Change month">
                    <a class="lm-tools-btn" href="{{ $base(['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}" aria-label="Previous month">‹</a>
                    <a class="lm-tools-btn" href="{{ $base(['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}" aria-label="Next month">›</a>
                    <a class="lm-tools-btn" href="{{ $base(['month' => $today->format('Y-m'), 'date' => $today->toDateString()]) }}">Today</a>
                </nav>
                <h2>{{ $month->format('F Y') }}</h2>
                <div class="lm-cal-views" role="group" aria-label="Calendar view">
                    @foreach($views as $key => $label)
                        <a class="{{ $view === $key ? 'is-on' : '' }}" href="{{ $base(['view' => $key]) }}" @if($view === $key) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            </header>

            @if($view === 'month')
                <div class="lm-cal-grid" role="grid" aria-label="{{ $month->format('F Y') }}">
                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)<div class="lm-cal-dow" role="columnheader">{{ $d }}</div>@endforeach
                    @foreach($weeks as $week)
                        @foreach($week as $day)
                            @php $items = $byDay[$day->toDateString()] ?? collect(); @endphp
                            <div role="gridcell" class="lm-cal-day {{ $day->isSameMonth($month) ? '' : 'is-out' }} {{ $day->isSameDay($today) ? 'is-today' : '' }} {{ $day->isSameDay($focus) ? 'is-on' : '' }}">
                                <a class="lm-cal-num" href="{{ $base(['date' => $day->toDateString(), 'month' => $day->format('Y-m')]) }}">{{ $day->day }}</a>
                                @foreach($items->take(3) as $r)
                                    <a class="lm-cal-item is-{{ $r['priority'] }}" href="{{ route('admin.leadmax.leads.show', $r['lead']->id) }}" title="{{ $r['subject'] }} — {{ $r['lead']->name }}{{ $r['agent'] ? ' · '.$r['agent'] : '' }}{{ $r['notes'] ? ': '.$r['notes'] : '' }}">
                                        <b>{{ $r['at']->format('g:i A') }}</b>
                                        <span>{{ $r['lead']->name }}</span>
                                        @if($r['agent'])<em>{{ $r['agent'] }}</em>@endif
                                    </a>
                                @endforeach
                                @if($items->count() > 3)
                                    <a class="lm-cal-more" href="{{ $base(['date' => $day->toDateString(), 'view' => 'day', 'month' => $day->format('Y-m')]) }}">+{{ $items->count() - 3 }} more</a>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @elseif($view === 'week')
                <div class="lm-cal-week" role="grid" aria-label="Week of {{ $focus->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('j M Y') }}">
                    @foreach(range(0, 6) as $i)
                        @php
                            $day = $focus->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->addDays($i);
                            $items = $byDay[$day->toDateString()] ?? collect();
                        @endphp
                        <div class="lm-cal-weekday {{ $day->isSameDay($today) ? 'is-today' : '' }}" role="gridcell">
                            <header><small>{{ $day->format('D') }}</small><b>{{ $day->day }}</b></header>
                            @forelse($items as $r)
                                <a class="lm-cal-item is-{{ $r['priority'] }}" href="{{ route('admin.leadmax.leads.show', $r['lead']->id) }}">
                                    <b>{{ $r['at']->format('g:i A') }}</b>
                                    <span>{{ $r['lead']->name }}</span>
                                    @if($r['agent'])<em>{{ $r['agent'] }}</em>@endif
                                </a>
                            @empty
                                <p class="lm-cal-none">—</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Day and List share one layout: the same rows, over a different window. --}}
                <div class="lm-cal-list">
                    <p class="lm-cal-list-head">{{ $rows->count() }} {{ \Illuminate\Support\Str::plural('result', $rows->count()) }}{{ $view === 'day' ? ' on '.$focus->format('D j M Y') : ' from today' }}</p>
                    @forelse($rows as $r)
                        <a class="lm-cal-row" href="{{ route('admin.leadmax.leads.show', $r['lead']->id) }}">
                            <time datetime="{{ $r['at']->toIso8601String() }}">
                                <b>{{ $r['at']->format('g:i A') }}</b>
                                <small>{{ $r['at']->format('D j M') }}</small>
                            </time>
                            <div>
                                <strong>{{ $r['subject'] }}</strong>
                                <span>{{ $r['lead']->name }}@if($r['agent']) · {{ $r['agent'] }}@endif</span>
                                @if($r['notes'])<p>{{ $r['notes'] }}</p>@endif
                            </div>
                            <span class="lm-tools-badge is-{{ $r['priority'] }}">{{ $priorities[$r['priority']] }}</span>
                        </a>
                    @empty
                        <p class="lm-tools-empty">Nothing booked in this window.</p>
                    @endforelse
                </div>
            @endif
        </section>

        <aside class="lm-tools-card lm-cal-rail">
            <header class="lm-cal-rail-head">
                <h2>My {{ strtolower($selectedType ? $eventTypes[$selectedType] : 'events') }} · {{ $focus->format('M j') }}</h2>
                <span>{{ $mine ? 'Assigned to you' : 'Everyone' }}@if($selectedAgent && $agents->isNotEmpty()) · {{ $agents->firstWhere('id', $selectedAgent)?->name }}@endif</span>
            </header>
            @forelse($dayRows as $r)
                <a class="lm-cal-meeting is-{{ $r['priority'] }}" href="{{ route('admin.leadmax.leads.show', $r['lead']->id) }}">
                    <strong>{{ $r['subject'] }}</strong>
                    <b>{{ $r['at']->format('g:i A') }}</b>
                    <span>{{ $r['lead']->name }}</span>
                    @if($r['notes'])<p>{{ $r['notes'] }}</p>@endif
                    <small>{{ $r['at']->format('M j') }} · {{ $r['agent'] ?: 'Unassigned' }}</small>
                </a>
            @empty
                <p class="lm-tools-empty">Nothing booked for {{ $focus->format('D j M') }}.</p>
            @endforelse
            @if($upcoming->isNotEmpty())
                <h3 class="lm-cal-rail-sub">Coming up</h3>
                @foreach($upcoming->take(4) as $r)
                    <a class="lm-cal-up" href="{{ route('admin.leadmax.leads.show', $r['lead']->id) }}">
                        <span class="lm-tools-badge is-{{ $r['priority'] }}">{{ $priorities[$r['priority']] }}</span>
                        <strong>{{ $r['subject'] }}</strong>
                        <small>{{ $r['lead']->name }} · {{ $r['at']->format('D j M, g:i A') }}@if($r['agent']) · {{ $r['agent'] }}@endif</small>
                    </a>
                @endforeach
            @endif
        </aside>
    </div>

    {{-- Add Event. A plain POST that redirects back, so it works without JS; the dialog is only
         the presentation. The lead is optional -- a campaign or an internal task need not be
         about anyone in particular -- but the agent it is assigned to is not. --}}
    <div class="lm-modal-backdrop lm-cal-dialog-backdrop" data-lm-cal-dialog hidden>
        <section class="lm-dialog lm-cal-dialog" role="dialog" aria-modal="true" aria-labelledby="lm-cal-dialog-title">
            <header class="lm-dialog-heading">
                <div>
                    <h2 id="lm-cal-dialog-title">Add event</h2>
                    <p>It appears on the calendar for whoever it is assigned to.</p>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.leadmax.calendar.event') }}" class="lm-cal-form">
                @csrf
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <label>Type
                    <select name="event_type" required>
                        @foreach($eventTypes as $key => $label)
                            <option value="{{ $key }}" @selected($selectedType === $key)>{{ \Illuminate\Support\Str::singular($label) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Subject<input name="subject" required maxlength="255" placeholder="e.g. Quarterly review call"></label>
                <label>Date<input name="reminder_date" type="date" required value="{{ $focus->toDateString() }}"></label>
                <label>Time<input name="reminder_time" type="time" required value="09:00"></label>
                <label>Assign to
                    <select name="agent_id" required>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) $selectedAgent === (string) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Priority
                    <select name="priority" required>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                {{-- Who the event is with. Was a plain select capped at 500 leads and
                     ordered by first name, so finding anyone meant scrolling and knowing
                     how their name was spelled. Typing searches name, email, phone or
                     account number, the same way the leads list does. --}}
                <label class="lm-cal-form-wide" data-lm-people>With <em>(optional)</em>
                    <input type="text" data-lm-people-input role="combobox" aria-expanded="false"
                           aria-autocomplete="list" aria-controls="lm-people-list" autocomplete="off"
                           placeholder="Type a name, email or phone — leave empty for an internal event">
                    <input type="hidden" name="lead_id" data-lm-people-value>
                    <ul id="lm-people-list" class="lm-people-list" role="listbox" hidden></ul>
                </label>
                <label class="lm-cal-form-wide">Notes<textarea name="notes" rows="2" maxlength="2000" placeholder="What is this for?"></textarea></label>
                <footer class="lm-dialog-footer">
                    <button type="button" class="lm-button" data-lm-cal-close>Cancel</button>
                    <button type="submit" class="lm-button primary">Add event</button>
                </footer>
            </form>
        </section>
    </div>
</div>
