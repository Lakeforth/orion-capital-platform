<x-admin-layout>
    <x-splade-modal max-width="3xl">
        <div class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Plans Embed Script</h2>
                <p class="mt-1 text-sm text-gray-500">Copy this script into the page where the plans widget should appear.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Embed URL</label>
                <input
                    type="text"
                    value="{{ $embedUrl }}"
                    readonly
                    class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="plans-embed-script" class="block text-sm font-medium text-gray-700">Script</label>
                    <button
                        type="button"
                        data-copy-plans-script
                        onclick="copyPlansEmbedScript(this)"
                        class="inline-flex items-center gap-2 rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <span>{{ svg('heroicon-s-clipboard-document','h-4') }}</span>
                        <span data-copy-label>Copy</span>
                    </button>
                </div>

                <textarea
                    id="plans-embed-script"
                    readonly
                    rows="14"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-3 font-mono text-sm leading-6 text-gray-900 shadow-sm subpixel-antialiased"
                >{{ $script }}</textarea>
            </div>
        </div>

        <x-splade-script>
            window.copyPlansEmbedScript = function(button) {
                const textarea = document.getElementById('plans-embed-script');
                const label = button.querySelector('[data-copy-label]');
                if (!textarea || !label) return;

                function showCopiedState() {
                    label.textContent = 'Copied';
                    setTimeout(function() {
                        label.textContent = 'Copy';
                    }, 2000);
                }

                function fallbackCopy() {
                    textarea.focus();
                    textarea.select();
                    textarea.setSelectionRange(0, textarea.value.length);

                    if (document.execCommand('copy')) {
                        showCopiedState();
                    }
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textarea.value)
                        .then(showCopiedState)
                        .catch(fallbackCopy);
                    return;
                }

                fallbackCopy();
            };
        </x-splade-script>
    </x-splade-modal>
</x-admin-layout>
