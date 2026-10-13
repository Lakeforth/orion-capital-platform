<x-admin-layout>
    <x-slot name="header">
        Campaign Management
    </x-slot>
    <x-slot name="header_right">
        <div class="p-1- flex justify-between gap-2">
            <Link class="bg-blue-900 text-white items-center rounded flex gap-2  px-2 py-2" modal
                  href="{{ route('admin.campaigns.create') }}">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>Add New</span>
            </Link>
        </div>
    </x-slot>

    <x-card>
        <x-splade-table class="mt-5" :for="$data">
            <x-splade-cell name as="$item">
                <Link modal href="{{ route('admin.campaigns.show', $item->id) }}" class="font-semibold text-blue-700 hover:underline cursor-pointer">{{ $item->name }}</Link>
            </x-splade-cell>

            <x-splade-cell source as="$item">
                <p>{{ ucwords($item->source) }}</p>
            </x-splade-cell>

            <x-splade-cell company.name as="$item">
                <p>{{ ucwords($item->company->name) }}</p>
            </x-splade-cell>

            <x-splade-cell last_lead_fetched as="$item">
                <p>{{ $item->last_lead_fetched ? $item->last_lead_fetched->diffForHumans() : '' }}</p>
            </x-splade-cell>

            <x-splade-cell updated_at as="$item">
                <p>{{ $item->updated_at ? $item->updated_at->format('M j, Y') : '' }}</p>
            </x-splade-cell>

            <x-splade-cell welcome_message_enabled as="$item">
                <Link method="POST" href="{{ route('admin.campaigns.toggle',['id' => $item->id, 'field' => 'welcome_message_enabled']) }}">
                    <label class="relative inline-flex items-center mb-5 cursor-pointer">
                        <input @if($item->welcome_message_enabled) checked @endif type="checkbox" value="" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </Link>
            </x-splade-cell>

            <x-splade-cell is_active as="$item">
                <Link method="POST" href="{{ route('admin.campaigns.toggle',['id' => $item->id, 'field' => 'is_active']) }}">
                    <label class="relative inline-flex items-center mb-5 cursor-pointer">
                        <input @if($item->is_active) checked @endif type="checkbox" value="" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </Link>
            </x-splade-cell>

            <x-splade-cell use_new_survey as="$item">
                @if($item->use_new_survey)<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Yes</span>@else<span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">No</span>@endif
            </x-splade-cell>

            <x-splade-cell send_to_third_party as="$item">@if($item->send_to_third_party)<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>@else<span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">Off</span>@endif</x-splade-cell>

            <x-splade-cell webhook_url as="$item">
                <div class="flex items-center space-x-2 w-full max-w-[150px]">
                    <span class="truncate text-gray-600 text-sm block" title="{{ $item->webhook_url }}">
                        {{ $item->webhook_url }}
                    </span>

                    <button
                        type="button"
                        id="copy-btn-{{ $item->id }}"
                        data-copy-url="{!! addslashes($item->webhook_url) !!}"
                        data-copy-id="{{ $item->id }}"
                            class="js-copy-btn inline-flex flex-shrink-0 text-gray-400 hover:text-blue-600 p-1 rounded hover:bg-gray-100 transition-colors focus:outline-none"
                        title="Copy URL"
                    >
                        <!-- Default Clipboard Icon -->
                        <span id="icon-clip-{{ $item->id }}">
                            {{ svg('heroicon-s-document-duplicate', 'h-4 w-4') }}
                        </span>
                        <!-- Hidden Checkmark Icon (Controlled by JS) -->
                        <span id="icon-check-{{ $item->id }}" class="text-green-500 hidden">
                            {{ svg('heroicon-s-clipboard-document-check', 'h-4 w-4') }}
                        </span>
                    </button>
                </div>
            </x-splade-cell>

            <x-splade-cell actions as="$item">

                <Link class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"
                   modal
                   href="{{ route('admin.campaigns.show', $item->id) }}">
                    {{ svg('heroicon-o-eye','h-4') }}
                </Link>

                <Link
                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    modal
                    href="{{ route('admin.campaigns.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>

                <Link
                    class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                    confirm="Delete"
                    confirm-text="Are you sure you want to delete this campaign?"
                    confirm-button="Delete"
                    cancel-button="Cancel"
                    href="{{ route('admin.campaigns.destroy', $item->id) }}" method="DELETE">
                    {{ svg('heroicon-o-trash','h-4') }}
                </Link>
            </x-splade-cell>
        </x-splade-table>
    </x-card>

    <x-splade-script>
        (function(){
            // Use event delegation so dynamically-updated rows still work
            document.addEventListener('click', function(event){
                const button = event.target.closest('.js-copy-btn');
                if (!button) return;

                // Strictly halt Splade's parent row routing/tab open interceptors
                event.preventDefault();
                event.stopPropagation();

                const url = button.getAttribute('data-copy-url');
                const id = button.getAttribute('data-copy-id');

                if (!url || !id) return;

                // Prefer modern Clipboard API, fallback to legacy approach
                function showCopiedState() {
                    const clipIcon = document.getElementById(`icon-clip-${id}`);
                    const checkIcon = document.getElementById(`icon-check-${id}`);

                    if (clipIcon && checkIcon) {
                        clipIcon.classList.add('hidden');
                        checkIcon.classList.remove('hidden');

                        setTimeout(() => {
                            clipIcon.classList.remove('hidden');
                            checkIcon.classList.add('hidden');
                        }, 2000);
                    }
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(() => {
                        showCopiedState();
                    }).catch(err => {
                        console.error('Failed to copy text via Clipboard API: ', err);
                        // fallthrough to legacy
                        fallbackCopyText(url, showCopiedState);
                    });
                } else {
                    fallbackCopyText(url, showCopiedState);
                }
            });

            function fallbackCopyText(text, onSuccess) {
                try {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();
                    textarea.setSelectionRange(0, textarea.value.length);
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    if (successful && typeof onSuccess === 'function') onSuccess();
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                }
            }
        })();
    </x-splade-script>
</x-admin-layout>
