{{-- LeadMax Calendar. The body lives in partials/calendar-content so the controller can hand
     it back on its own when the page asks for just the fragment. --}}
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@include('admin.leadmax.partials.calendar-content')
</x-leadmax-layout>
