<x-admin-layout>
    <x-slot name="header">
        Upload Default Images
    </x-slot>


    <x-card>

        <x-splade-form class="space-y-2" action="{{ route('admin.image.upload.store') }}">


            <x-splade-file accept="image/jpeg, image/jpg, image/png" preview multiple label="Gallery Images (optional)"
                           name="images" filepond max-size="5MB" />


            <x-splade-submit class="mt-3 bg-primary text-white"  label="Upload" />


        </x-splade-form>
    </x-card>
</x-admin-layout>
