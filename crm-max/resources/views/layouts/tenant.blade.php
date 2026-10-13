<div class="min-h-screen bg-gradient-to-br from-white via-whiten to-gray">
    <div class="flex min-h-screen flex-col">
        @include('layouts.partials.tenant-header')

        <main class="flex-1">
            <div class="mx-auto max-w-screen-2xl p-5 md:p-7 2xl:p-10">
                @if(isset($header))
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center justify-between">
                        <h2 class="text-xl sm:text-2xl font-semibold text-black">
                            {{ $header }}
                        </h2>

                        @isset($header_right)
                            {{ $header_right }}
                        @else
                            <nav>
                                <ol class="flex items-center gap-2 text-sm text-body">
                                    <li><a class="font-medium text-bodydark2" href="{{ route('super.dashboard') }}">Dashboard /</a></li>
                                    <li class="text-danger">{{ $header }}</li>
                                </ol>
                            </nav>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</div>

<x-splade-script>
    initFlowbite();
</x-splade-script>
