<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
    @php
        $amount = fn ($value) => number_format((float) $value, 0, '.', ' ');
        $periodQuery = array_filter(['period' => $period, 'from' => request('from'), 'to' => request('to')]);
        $leadsUrl = route('admin.leadmax.leads', $periodQuery);
        $depositsUrl = route('admin.leadmax.deposits', [...$periodQuery, 'status' => 'approved']);
        $withdrawalsUrl = route('admin.leadmax.withdrawals', [...$periodQuery, 'status' => 'approved']);
        $currentDepositsUrl = route('admin.leadmax.deposits', ['period' => 'current_month', 'status' => 'approved']);
        $currentWithdrawalsUrl = route('admin.leadmax.withdrawals', ['period' => 'current_month', 'status' => 'approved']);
        $cards = [
            ['Registrations', $amount($metrics['registrations']), 'This month: '.$amount($currentMetrics['registrations']), 'imgContainer14', 'green', $leadsUrl, route('admin.leadmax.leads', ['period' => 'current_month'])],
            ['Deposits', '$'.$amount($metrics['deposit_amount']), 'This month: $'.$amount($currentMetrics['deposit_amount']), null, 'blue', $depositsUrl, $currentDepositsUrl],
            ['Deposits count', $amount($metrics['deposit_count']), 'This month: '.$amount($currentMetrics['deposit_count']), 'imgContainer15', 'blue', $depositsUrl, $currentDepositsUrl],
            ['Withdrawals', '$'.$amount($metrics['withdrawal_amount']), 'This month: $'.$amount($currentMetrics['withdrawal_amount']), 'imgContainer16', 'pink', $withdrawalsUrl, $currentWithdrawalsUrl],
            ['Withdrawals count', $amount($metrics['withdrawal_count']), 'This month: '.$amount($currentMetrics['withdrawal_count']), 'imgContainer17', 'pink', $withdrawalsUrl, $currentWithdrawalsUrl],
        ];
        $badgeIcons = ['registration-badge','deposit-badge','deposit-count-badge','withdrawal-badge','withdrawal-count-badge'];
        $leadId = fn ($user) => $user?->account_id ?: substr($user?->id ?? '', 0, 8);
    @endphp
    <div class="leadmax-dashboard" data-reporting-date="{{ $reportingDate }}">
        <h1 class="leadmax-dashboard-title">Dashboard</h1>
        <div class="leadmax-filters">
            <form class="leadmax-periods leadmax-dashboard-periods" method="GET">
                @foreach(['today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'] as $value=>$label)
                    <button type="submit" name="period" value="{{ $value }}" class="{{ $period === $value ? 'is-selected' : '' }}" aria-pressed="{{ $period === $value ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
                <div class="leadmax-custom-period"><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="from" value="{{ request('from') }}" placeholder="From date" aria-label="From date" data-leadmax-date></label><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="to" value="{{ request('to') }}" placeholder="To date" aria-label="To date" data-leadmax-date></label><button class="sr-only" type="submit" name="period" value="custom">Apply custom dates</button></div>
            </form>
            <div class="leadmax-breadcrumb"><span>CRM</span><span>/</span><strong>Dashboard</strong></div>
        </div>
        <section class="leadmax-kpis" aria-label="LeadMax summary">
            @foreach($cards as [$label,$value,$summary,$icon,$tone,$url,$currentUrl])
                <article class="leadmax-kpi-card lm-card-link">
                    <div class="leadmax-kpi-top"><div class="leadmax-kpi-copy"><p>{{ $label }} {{ $periodLabel }}</p><a href="{{ $url }}" class="leadmax-kpi-link lm-card-main" aria-label="{{ $label }} {{ $periodLabel }}: {{ $value }}"><strong>{{ $value }}</strong></a><x-leadmax-reference-icon :name="$badgeIcons[$loop->index]" /></div></div>
                    <small><a href="{{ $currentUrl }}" class="leadmax-kpi-link lm-card-extra">{{ $summary }}</a></small>
                </article>
            @endforeach
        </section>
        <section class="leadmax-panel-grid">
            <section class="leadmax-panel" aria-label="Performance"><header><strong>Performance</strong><button type="button" data-leadmax-panel aria-label="Collapse Performance" aria-expanded="true"><x-leadmax-reference-icon name="collapse" /></button></header><div class="leadmax-table-wrap"><table class="leadmax-performance-table"><colgroup><col style="width:17.7%"><col style="width:17%"><col style="width:17%"><col style="width:18.6%"><col style="width:29.7%"></colgroup><thead><tr>@foreach(['Name','Leads',"FTD's",'Rate,%','Deposit amount,$'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
                @forelse($performance as $manager)@php $managerLeadsUrl = route('admin.leadmax.leads', [...$periodQuery, 'manager_id' => $manager->id]); @endphp<tr><td><a class="leadmax-kpi-link" href="{{ route('admin.leadmax.agents.show', $manager->id) }}"><i class="leadmax-manager-dot"></i>{{ $manager->name }}</a></td><td><a class="leadmax-kpi-link" href="{{ $managerLeadsUrl }}">{{ $manager->leads_count }}</a></td><td><a class="leadmax-kpi-link" href="{{ route('admin.leadmax.first-time-deposits', [...$periodQuery, 'manager_id' => $manager->id]) }}">{{ $manager->ftd_count }}</a></td><td>{{ $manager->leads_count ? number_format($manager->ftd_count / $manager->leads_count * 100, 2) : '0.00' }}</td><td><a class="leadmax-kpi-link" href="{{ route('admin.leadmax.deposits', [...$periodQuery, 'status' => 'approved', 'manager_id' => $manager->id]) }}">{{ number_format((float) $manager->deposit_amount, 0, '.', '') }}</a></td></tr>@empty<tr class="leadmax-empty"><td colspan="5"><div class="leadmax-empty-state"><x-leadmax-reference-icon name="empty-performance" /><strong>No data yet</strong><p>Performance data will appear here</p></div></td></tr>@endforelse
            </tbody></table></div></section>
            <section class="leadmax-panel leadmax-new-leads" aria-label="New Leads"><header><strong>New Leads</strong><button type="button" data-leadmax-panel aria-label="Collapse New Leads" aria-expanded="true"><x-leadmax-reference-icon name="collapse" /></button></header><div class="leadmax-table-wrap"><table><colgroup><col style="width:13.4%"><col style="width:16.8%"><col style="width:15.9%"><col style="width:14.3%"><col style="width:20.5%"><col style="width:19.1%"></colgroup><thead><tr>@foreach(['LeadID','Name','Phone','Country','Affiliate','Reg Date'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
                @forelse($newLeads as $lead)@php $leadPhone = \App\Support\LeadMax\Phone::parts($lead->phone, $lead->country); @endphp<tr><td><a href="{{ route('admin.leadmax.leads.show', $lead->id) }}">{{ $leadId($lead) }}</a></td><td><span dir="auto">{{ $lead->name }}</span></td><td>@if($leadPhone['raw'] !== '')<span class="lm-copyable" data-lm-copy-text="{{ $leadPhone['raw'] }}" title="Copy {{ $leadPhone['raw'] }}">{{ $leadPhone['display'] }}</span>@endif</td><td><span class="lm-country"><x-leadmax-flag :country="$lead->country" />{{ $lead->country ?: 'Unknown' }}</span></td><td>{{ $lead->affiliate?->name ?: 'Direct' }}</td><td title="{{ $lead->created_at?->format('Y-m-d H:i:s') }}">{{ $lead->created_at?->format('Y-m-d H') }}...</td></tr>@empty<tr class="leadmax-empty"><td colspan="6"><div class="leadmax-empty-state"><x-leadmax-reference-icon name="empty-new-leads" /><strong>No data yet</strong><p>New leads will appear here</p></div></td></tr>@endforelse
            </tbody></table></div></section>
            <section class="leadmax-panel" aria-label="Affiliate Delivery"><header><strong>Affiliate Delivery</strong><button type="button" data-leadmax-panel aria-label="Collapse Affiliate Delivery" aria-expanded="true"><x-leadmax-reference-icon name="collapse" /></button></header><div class="leadmax-table-wrap"><table class="leadmax-affiliate-table"><thead><tr><th>Affiliate Name</th><th>Leads Count</th></tr></thead><tbody>
                @forelse($affiliateDelivery as $affiliate)<tr><td><a class="leadmax-kpi-link" href="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}">{{ $affiliate->name }}</a></td><td><a class="leadmax-kpi-link" href="{{ route('admin.leadmax.leads', [...$periodQuery, 'created_by' => $affiliate->id]) }}">{{ $affiliate->leads_count }}</a></td></tr>@empty<tr class="leadmax-empty"><td colspan="2"><div class="leadmax-empty-state"><x-leadmax-reference-icon name="empty-affiliates" /><strong>No data yet</strong><p>Affiliate delivery data will appear here</p></div></td></tr>@endforelse
            </tbody></table></div></section>
            <section class="leadmax-panel" aria-label="Last comments"><header><strong>Last comments</strong><button type="button" data-leadmax-panel aria-label="Collapse Last comments" aria-expanded="true"><x-leadmax-reference-icon name="collapse" /></button></header><div class="leadmax-table-wrap"><table class="leadmax-notes-table"><colgroup><col style="width:19.1%"><col style="width:19.6%"><col style="width:25.7%"><col style="width:35.6%"></colgroup><thead><tr>@foreach(['LeadID','Sales/Ret','Comment','Date'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
                @forelse($latestNotes as $note)<tr><td>@if($note->user)<a href="{{ route('admin.leadmax.leads.show', ['id' => $note->user->id, 'tab' => 'comments']) }}">{{ $leadId($note->user) }}</a>@endif</td><td>{{ $note->by?->name ?: 'System' }}</td><td title="{{ $note->note }}">{{ $note->note }}</td><td>{{ $note->created_at?->format('Y-m-d H:i:s') }}</td></tr>@empty<tr class="leadmax-empty"><td colspan="4"><div class="leadmax-empty-state"><x-leadmax-reference-icon name="empty-comments" /><strong>No data yet</strong><p>Comments will appear here</p></div></td></tr>@endforelse
            </tbody></table></div></section>
        </section>
    </div>
</x-leadmax-layout>
