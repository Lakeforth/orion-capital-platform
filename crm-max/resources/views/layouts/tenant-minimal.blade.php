@php($siteName = get_site_name())

<div class="min-h-screen bg-gradient-to-br from-white via-whiten to-gray">
    <header class="border-b border-stroke bg-white/70 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-danger text-white font-bold">
                    {{ strtoupper(substr($siteName, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm uppercase tracking-widest text-danger">Owner Console</p>
                    <h1 class="text-lg font-semibold text-black">{{ $siteName }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-medium text-black">{{ auth()->user()->name ?: auth()->user()->email }}</p>
                    <p class="text-xs text-body">Account Owner</p>
                </div>
                <Link href="{{ route('super.logout') }}" method="POST"
                      class="inline-flex items-center gap-2 rounded-full bg-danger px-4 py-2 text-sm font-semibold text-white shadow hover:bg-danger/90">
                    {{ svg('heroicon-o-arrow-left-on-rectangle','h-4 w-4') }}
                    Logout
                </Link>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>
</div>
