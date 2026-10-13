<div class="grid grid-cols-3 gap-3">


    <div class="p-6 mt-2 rounded-md shadow-md bg-white">

        <h3 class="font-bold text-xl">Basic Details</h3>

        <x-splade-form stay :default="$user" class="space-y-2 mt-2" method="PUT" action="{{ route('admin.users.update', $user->id) }}">

            <x-splade-input required label="First Name" name="first_name" />

            <x-splade-input required label="Last Name" name="last_name" />

            <x-splade-input required label="Email" name="email" />

            <x-splade-input required label="Phone" name="phone" />

            <x-splade-input label="Password (leave empty to retain old password)" name="password" data-lm-pw-generate />

            <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

        </x-splade-form>

    </div>
    <div class="p-6 mt-2 rounded-md shadow-md bg-white">

        <h3 class="font-semibold text-xl">Assignee</h3>

        <x-splade-form stay :default="$user" class="space-y-2 mt-2" method="POST" action="{{ route('admin.user.update_user', $user->id) }}">



            <x-splade-select choices option-label="name"
                             option-value="id" label="Agent"
                             remote-root="data"
                             remote-url="/api/managers"
                             name="manager_id"/>

            <x-splade-select label="Team" name="team" :options="get_teams()" />

            <x-splade-select label="Select Desk" name="desk" :options="get_desks()" />






            <x-splade-submit class="mt-3" class="mt-3" label="Update" />

        </x-splade-form>

    </div>
    <div class="p-6 mt-2 rounded-md shadow-md bg-white">

        <h3 class="font-semibold text-xl">Company and Label</h3>

        <x-splade-form :default="$user" class="space-y-2" method="POST" action="{{ route('admin.user.update_user', $user->id) }}">


                <x-splade-input  label="Pin Code" name="pin_key" />

            <x-splade-select label="Select Label" name="label" :options="get_labels()" />

            <x-splade-select label="Internal Brand" name="brand" :options="get_brands()" />


            <x-splade-select label="Select Status" name="status" :options="get_lead_status()" />


            <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

        </x-splade-form>

    </div>
    <div class="p-6 mt-2 rounded-md shadow-md bg-white">

        <h3 class="font-semibold text-xl">Data Management</h3>

        <p>
            Remove this customer's chart if he requested that,
            if not please be aware that what has been deleted
            can never brought back
        </p>

        <x-splade-form :default="$user" class="space-y-2" method="PUT" action="{{ route('admin.users.update', $user->id) }}">

            <x-splade-submit class="mt-3" class="mt-3" label="Delete Account" />

        </x-splade-form>
    </div>

    <div class="p-6 mt-2 rounded-md shadow-md bg-white">

        <h3 class="font-semibold text-xl">Trading Controls</h3>

        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Manual Trading</p>
                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'can_trade']) }}"
                      class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-white {{ $user->can_trade ? 'bg-red-500' : 'bg-[#0d63cc]' }}">
                    {{ $user->can_trade ? 'Disable Trade' : 'Enable Trade' }}
                </Link>
            </div>

            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Auto Trader</p>
                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'copy_trader']) }}"
                      class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-white {{ $user->copy_trader ? 'bg-red-500' : 'bg-[#0d63cc]' }}">
                    {{ $user->copy_trader ? 'Disable Auto Trader' : 'Enable Auto Trader' }}
                </Link>
            </div>

            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600">AI Trading</p>
                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'ai_trade']) }}"
                      class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-white {{ $user->ai_trade ? 'bg-red-500' : 'bg-[#0d63cc]' }}">
                    {{ $user->ai_trade ? 'Disable AI Trading' : 'Enable AI Trading' }}
                </Link>
            </div>
        </div>
    </div>
</div>
