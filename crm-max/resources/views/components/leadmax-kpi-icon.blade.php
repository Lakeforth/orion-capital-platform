@props(['name'])

<span {{ $attributes->class(['lm-kpi-icon', 'lm-kpi-icon-'.$name]) }} aria-hidden="true">
    @switch($name)
        @case('total')
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>
            @break
        @case('new')
            <svg viewBox="0 0 24 24"><path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM19 8v6M22 11h-6"/></svg>
            @break
        @case('contacted')
            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92Z"/></svg>
            @break
        @case('qualified')
            <svg viewBox="0 0 24 24"><path d="m12 2 2.1 2.1 3-.43.43 3L19.65 9 18 11.5l.75 2.94-2.8 1.12-.97 2.84L12 18l-2.98.4-.97-2.84-2.8-1.12L6 11.5 4.35 9l2.12-2.33.43-3 3 .43L12 2Z"/><path d="m9 11 2 2 4-4"/></svg>
            @break
        @case('converted')
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
            @break
        @case('disqualified')
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
            @break
    @endswitch
</span>
