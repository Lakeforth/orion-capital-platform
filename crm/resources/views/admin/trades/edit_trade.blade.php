<x-admin-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit trade
        </h2>
    <div class="py-12">

        <x-splade-form class="space-y-2" :default="$default" method="PUT" action="{{ route('admin.trades.update', $trade->id) }}">


            <div class="grid grid-cols-2 gap-2">

                <x-splade-input date time
                                date-format="Y-m-d"
                                time-format="H:i"
                                name="opened_at" label="Open Time" required />

                <x-splade-input date time
                                date-format="Y-m-d"
                                time-format="H:i"
                                name="close_at" label="Close Time" />


                <x-splade-input step="any" name="volume" label="Volume" type="number" required />

                <x-splade-input step="any" name="leverage" label="Leverage" type="number" required />

                <x-splade-input step="any" name="opening_price" label="Opening Price" type="number" required />
                <x-splade-input step="any" name="closing_price" label="Closing Price" type="number" required />
                <x-splade-input step="any" name="margin" label="Margin" type="number" required />
                <x-splade-input step="any" name="profit" label="Profit" type="number" required />

            </div>


            <x-splade-submit class="mt-3" label="Update" />

        </x-splade-form>
    </div>
    </x-splade-modal>
</x-admin-layout>
