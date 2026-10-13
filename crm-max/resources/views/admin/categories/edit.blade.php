<x-app-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit category
        </h2>
    <div class="py-12">

        <x-splade-form class="space-y-2" :default="$category" method="PUT" action="{{ route('admin.categories.update', $category) }}">


            @include('admin.categories.cat_form',['action' => 'update','type' => 'category'])


            <x-splade-submit class="mt-3" label="Update" />

        </x-splade-form>
    </div>
    </x-splade-modal>
</x-app-layout>
