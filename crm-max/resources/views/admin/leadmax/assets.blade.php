{{-- LeadMax Assets. The body lives in partials/assets-content so the controller can hand it
     back on its own when the page asks for just the fragment. --}}
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@include('admin.leadmax.partials.assets-content')
</x-leadmax-layout>
