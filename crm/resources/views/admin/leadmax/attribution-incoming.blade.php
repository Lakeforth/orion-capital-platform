<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
    <div class="lm-attr-incoming-tabs"><div class="lm-leads-title"><h1>Attribution</h1><nav class="lm-set-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><span>CRM</span><i>/</i><b>Attribution</b></nav></div>@include('admin.leadmax.partials.attribution-tabs', ['attributionView' => 'incoming'])</div>
    @include('admin.leadmax.partials.leads-content')
</x-leadmax-layout>
