@php
    $demoPlatformId = 'demo-platform-'.md5((string) ($demoPlatform['next_reset_at'] ?? now()->toIso8601String()));
    $cycleSeconds = max(1, ((int) $demoPlatform['reset_interval_hours']) * 3600);
@endphp

<div id="{{ $demoPlatformId }}"
     data-next-reset-at="{{ $demoPlatform['next_reset_at'] }}"
     data-cycle-seconds="{{ $cycleSeconds }}"
     class="relative z-[220]">
    <div class="relative">
        <button type="button"
                data-demo-toggle
                class="group relative flex h-[38px] w-[38px] items-center justify-center rounded-[0.8rem] bg-[#1E2235] shadow-[0_10px_28px_rgba(15,23,42,0.22)] ring-1 ring-white/10 transition duration-200 hover:-translate-y-0.5 hover:bg-[#252a41]"
                aria-expanded="false"
                aria-controls="{{ $demoPlatformId }}-panel">
            <svg class="absolute inset-0 h-[38px] w-[38px] -rotate-90" viewBox="0 0 44 44" aria-hidden="true">
                <circle cx="22" cy="22" r="16" fill="none" stroke="rgba(148,163,184,0.26)" stroke-width="3"></circle>
                <circle data-demo-ring
                        cx="22"
                        cy="22"
                        r="16"
                        fill="none"
                        stroke="rgb(251 191 36)"
                        stroke-linecap="round"
                        stroke-width="3"
                        stroke-dasharray="100.53"
                        stroke-dashoffset="0"></circle>
            </svg>
            <span data-demo-short class="relative text-[10px] font-semibold text-white">--</span>
        </button>

        <div id="{{ $demoPlatformId }}-panel"
             data-demo-panel
             class="absolute right-0 top-12 z-[1000] hidden w-[min(21rem,calc(100vw-2rem))] rounded-[1rem] border border-slate-200 bg-white p-4 shadow-[0_24px_60px_rgba(15,23,42,0.18)] ring-1 ring-slate-950/5 sm:w-80">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Demo Platform</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-900">
                        Data resets in <span data-demo-remaining class="text-amber-700">calculating...</span>
                    </p>
                </div>
                <button type="button"
                        data-demo-close
                        class="inline-flex h-8 w-8 items-center justify-center rounded-[0.7rem] text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close demo details">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-[0.85rem] bg-slate-50 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Next reset</p>
                    <p data-demo-next-reset class="mt-2 text-sm font-semibold text-slate-800">{{ \Carbon\Carbon::parse($demoPlatform['next_reset_at'])->format('M d, H:i') }}</p>
                </div>
                <div class="rounded-[0.85rem] bg-slate-50 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Interval</p>
                    <p class="mt-2 text-sm font-semibold text-slate-800">{{ $demoPlatform['reset_interval_hours'] }} hours</p>
                </div>
            </div>

        </div>
    </div>
</div>

@if(filled($demoPlatform['whatsapp_link']) || filled($demoPlatform['telegram_link']))
    <div class="fixed bottom-5 right-5 z-[120] flex flex-col gap-2 sm:bottom-6 sm:right-6">
        @if(filled($demoPlatform['telegram_link']))
            <a href="{{ $demoPlatform['telegram_link'] }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#229ED9] text-white shadow-[0_14px_30px_rgba(34,158,217,0.34)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#1d8fc5]"
               aria-label="Open Telegram support">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M9.04 15.47 8.7 20.3c.49 0 .7-.21.95-.46l2.29-2.19 4.75 3.48c.87.48 1.49.23 1.72-.81l3.12-14.63.01-.01c.27-1.25-.45-1.74-1.3-1.43L1.89 11.32c-1.23.48-1.21 1.17-.21 1.48l4.69 1.46L17.26 7.4c.52-.34.99-.15.6.19"/>
                </svg>
            </a>
        @endif
        @if(filled($demoPlatform['whatsapp_link']))
            <a href="{{ $demoPlatform['whatsapp_link'] }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_14px_30px_rgba(37,211,102,0.34)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#1ebe5b]"
               aria-label="Open WhatsApp support">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.6 2 2.2 6.4 2.2 11.83c0 1.74.45 3.43 1.31 4.92L2 22l5.4-1.42a9.8 9.8 0 0 0 4.63 1.18h.01c5.42 0 9.82-4.4 9.82-9.83a9.77 9.77 0 0 0-2.81-7.02Zm-7.01 15.19h-.01a8.14 8.14 0 0 1-4.14-1.13l-.3-.18-3.2.84.85-3.12-.2-.32a8.13 8.13 0 0 1-1.25-4.36c0-4.49 3.65-8.14 8.15-8.14 2.18 0 4.23.85 5.77 2.39a8.1 8.1 0 0 1 2.38 5.77c0 4.49-3.65 8.15-8.05 8.15Zm4.46-6.1c-.24-.12-1.42-.7-1.64-.79-.22-.08-.38-.12-.54.12-.16.24-.62.79-.76.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.18-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.01-.36.11-.48.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.31-.74-1.79-.2-.48-.4-.42-.54-.43l-.46-.01c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.09 3.61.57.25 1.02.4 1.37.51.58.18 1.1.15 1.51.09.46-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/>
                </svg>
            </a>
        @endif
    </div>
@endif

<x-splade-script>
    (function () {
        var root = document.getElementById(@js($demoPlatformId));
        if (!root) {
            return;
        }

        var nextReset = new Date(root.dataset.nextResetAt);
        var cycleSeconds = Math.max(1, parseInt(root.dataset.cycleSeconds || '1', 10));
        var reloadUrl = new URL(window.location.href);
        var reloadedForCycle = reloadUrl.searchParams.get('demoReloaded') === root.id;
        var reloadScheduled = false;
        var hasSeenFutureState = false;
        var previousRemainingSeconds = null;
        var toggleButton = root.querySelector('[data-demo-toggle]');
        var panel = root.querySelector('[data-demo-panel]');
        var closeButton = root.querySelector('[data-demo-close]');
        var remainingEl = root.querySelector('[data-demo-remaining]');
        var nextResetEl = root.querySelector('[data-demo-next-reset]');
        var shortEl = root.querySelector('[data-demo-short]');
        var ringEl = root.querySelector('[data-demo-ring]');
        var circumference = 100.53;

        var formatRemaining = function (seconds) {
            if (seconds <= 0) {
                return 'less than a minute';
            }

            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = seconds % 60;
            var parts = [];

            if (hours > 0) {
                parts.push(hours + 'h');
            }

            if (minutes > 0 || hours > 0) {
                parts.push(minutes + 'm');
            }

            parts.push(secs + 's');

            return parts.join(' ');
        };

        var formatShort = function (seconds) {
            if (seconds >= 3600) {
                return Math.ceil(seconds / 3600) + 'h';
            }

            if (seconds >= 60) {
                return Math.ceil(seconds / 60) + 'm';
            }

            return seconds + 's';
        };

        var stripReloadMarker = function () {
            var currentUrl = new URL(window.location.href);
            if (!currentUrl.searchParams.has('demoReloaded')) {
                return;
            }

            currentUrl.searchParams.delete('demoReloaded');
            window.history.replaceState({}, '', currentUrl.toString());
            reloadedForCycle = false;
        };

        var triggerReload = function () {
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('demoReloaded', root.id);
            window.location.replace(currentUrl.toString());
        };

        var advanceToNextCycle = function () {
            while (nextReset.getTime() <= Date.now()) {
                nextReset = new Date(nextReset.getTime() + cycleSeconds * 1000);
            }

            root.dataset.nextResetAt = nextReset.toISOString();
        };

        var showPanel = function () {
            root.classList.add('z-[1000]');
            panel.classList.remove('hidden');
            toggleButton.setAttribute('aria-expanded', 'true');
        };

        var hidePanel = function () {
            panel.classList.add('hidden');
            root.classList.remove('z-[1000]');
            toggleButton.setAttribute('aria-expanded', 'false');
        };

        var render = function () {
            var now = new Date();
            var remainingSeconds = Math.max(0, Math.floor((nextReset.getTime() - now.getTime()) / 1000));

            if (remainingSeconds <= 0 && !reloadedForCycle) {
                advanceToNextCycle();
                remainingSeconds = Math.max(0, Math.floor((nextReset.getTime() - now.getTime()) / 1000));
            }

            if (remainingSeconds > 1) {
                hasSeenFutureState = true;
            }

            if (reloadedForCycle && remainingSeconds > 1) {
                stripReloadMarker();
            }

            if (
                remainingSeconds <= 1
                && previousRemainingSeconds !== null
                && previousRemainingSeconds > 1
                && hasSeenFutureState
                && !reloadedForCycle
                && !reloadScheduled
            ) {
                reloadScheduled = true;
                triggerReload();
                return;
            }

            if (remainingSeconds <= 0 && reloadedForCycle) {
                advanceToNextCycle();
                stripReloadMarker();
                remainingSeconds = Math.max(0, Math.floor((nextReset.getTime() - now.getTime()) / 1000));
            }

            var progress = remainingSeconds / cycleSeconds;
            var dashOffset = circumference * (1 - Math.max(0, Math.min(1, progress)));

            if (remainingEl) {
                remainingEl.textContent = formatRemaining(remainingSeconds);
            }

            if (shortEl) {
                shortEl.textContent = formatShort(remainingSeconds);
            }

            if (ringEl) {
                ringEl.style.strokeDashoffset = String(dashOffset);
            }

            if (nextResetEl && !Number.isNaN(nextReset.getTime())) {
                nextResetEl.textContent = nextReset.toLocaleString([], {
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }

            root.dataset.nextResetAt = nextReset.toISOString();
            previousRemainingSeconds = remainingSeconds;
        };

        toggleButton.addEventListener('click', function () {
            if (panel.classList.contains('hidden')) {
                showPanel();
            } else {
                hidePanel();
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', hidePanel);
        }

        document.addEventListener('click', function (event) {
            if (! root.contains(event.target)) {
                hidePanel();
            }
        });

        render();
        setInterval(render, 1000);
    })();
</x-splade-script>
