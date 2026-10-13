<div class="mt-2 bg-white p-4 rounded shadow-sm">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Trader Setting
    </h2>
    <div class="py-6">

        <div class="flex justify-between align-items-center">
            <div class="flex-1">
                <x-splade-form :default="$user" class="space-y-2 mt-2" method="PUT" action="{{ route('admin.users.update', $user->id) }}">


                    <x-splade-select label="Copy Trader" name="copy_trader" :options="get_active_status()" />
                    <x-splade-select label="Trading Status" name="can_trade" :options="get_active_status()" />


                    <x-splade-submit class="mt-3" class="mt-3" label="Update" />

                </x-splade-form>

            </div>

            <div class="flex-1">
                <div class="flex justify-end">
                    <a href="" class="py-2 px-3 bg-black text-white rounded">
                        Login to trader
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
