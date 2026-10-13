<x-app-layout>
    <x-slot name="header">
        <div class="flex ">
            <h2 class="dark:text-white text-lg text-gray-800 leading-tight">
                Editing post
            </h2>
        </div>

    </x-slot>
    <x-card>
        <div class="py-4">

            <x-splade-form :default="$post" class="space-y-2" method="PUT" action="{{ route('admin.posts.update', $post->id) }}">

                <div class="grid md:grid-cols-4 gap-4">
                    <div class="md:col-span-3 shadow rounded space-y-3 bg-blue-500- p-3">
                        <x-splade-input required label="Title" name="title" />


                        <x-splade-wysiwyg label="Detail" name="text" />


                        <x-splade-textarea label="Excerpt" name="description" autosize />


                    </div>

                    <div class="md:col-span-1 shadow rounded space-y-3 bg-blue-500- p-3">

                        <x-splade-file name="featured_image" preview label="Featured Image"
                                       filepond min-size="100KB" max-size="5MB" />


                        <x-splade-input label="Tags (separate with comma)" name="tags" />


                        <x-splade-select name="categories[]" label="Categories"
                                         option-label="name" option-value="id"
                                         remote-url="{{ route('api.categories.index') }}" remote-root="data" relation multiple choices />


                        <x-splade-submit class="mt-3" class="mt-3" label="Publish" />
                    </div>



                </div>
            </x-splade-form>

        </div>
    </x-card>
</x-app-layout>
