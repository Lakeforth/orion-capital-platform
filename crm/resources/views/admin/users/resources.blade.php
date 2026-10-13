<x-admin-layout>

    <x-card>

        <div class="p-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Queek Resources
            </h2>
            <div class="flow-root mt-5">




                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                        <li class="me-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="videos-tab" data-tabs-target="#videos" type="button" role="tab" aria-controls="videos" aria-selected="false">Videos</button>
                        </li>
                        <li class="me-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="dashboard-tab" data-tabs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="false">Docs</button>
                        </li>

                    </ul>
                </div>
                <div id="default-tab-content">
                    <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="videos" role="tabpanel" aria-labelledby="videos-tab">

                        <div class="grid grid-cols-1 gap-4">
                            @foreach($videos as $item)
                                <div class="p-4 bg-gray-300 rounded">
                                    <p>{{ $item['title'] }}</p>

                                    <div class="mt-4">
                                        <iframe width="560" height="315" src="{{ $item['link'] }}" title="{{ $item['title'] }}"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media;
                                                gyroscope; picture-in-picture; web-share"
                                                referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                                        </iframe>

                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>

                    <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No resources uploaded for you yet
                            <strong class="font-medium text-gray-800 dark:text-white"></strong></p>
                    </div>
                </div>


            </div>
        </div>
    </x-card>
</x-admin-layout>
