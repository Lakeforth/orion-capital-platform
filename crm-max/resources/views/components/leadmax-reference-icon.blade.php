@props(['name'])
@php
    // Pixel coordinates of artwork in the approved reference images, not dashboard content.
    $icons = [
        'nav-back' => [15,65,18,18], 'nav-home' => [15,101,18,18], 'nav-section' => [15,136,14,16], 'nav-up' => [184,139,14,12], 'nav-down' => [184,572,14,12],
        'nav-leads' => [40,170,16,16], 'nav-affiliates' => [40,200,16,16], 'nav-attribution' => [40,260,16,16], 'nav-agents' => [40,286,16,16], 'nav-managers' => [42,316,14,16], 'nav-open' => [33,352,25,12], 'nav-trades' => [33,382,25,14],
        'nav-finances' => [15,413,16,14], 'nav-calendar' => [15,445,18,14], 'nav-splitter' => [15,475,16,13], 'nav-upload' => [15,505,18,13], 'nav-assets' => [15,537,16,14], 'nav-markups' => [15,568,18,16], 'nav-settings' => [15,598,18,16], 'nav-report' => [40,632,10,10],
        'registrations' => [255,129,55,55], 'deposits' => [567,129,55,55], 'deposit-count' => [875,129,55,55],
        'withdrawals' => [1185,129,55,55], 'withdrawal-count' => [1495,129,55,55],
        'registration-badge' => [338,178,28,28], 'deposit-badge' => [651,178,28,28], 'deposit-count-badge' => [960,178,28,28],
        'withdrawal-badge' => [1269,178,28,28], 'withdrawal-count-badge' => [1578,178,28,28],
        'performance' => [248,272,24,24], 'new-leads' => [912,272,24,24], 'affiliates' => [248,580,28,24], 'comments' => [912,580,24,24],
        'collapse' => [856,277,14,14], 'sort' => [294,317,13,12], 'calendar' => [794,70,14,16],
        'menu' => [232,16,16,18], 'search' => [1436,13,24,26], 'bell' => [1478,13,24,26], 'theme' => [1560,13,24,26], 'user' => [1618,13,24,26], 'chevron' => [1759,19,10,12],
        'empty-performance' => [532,402,56,44], 'empty-new-leads' => [1284,402,56,44], 'empty-affiliates' => [529,692,64,50], 'empty-comments' => [1283,687,58,55],
    ];
    // The reference artwork has no icon for these (their crops landed on neighbouring text, e.g. "Fir"/"Ca"),
    // so they are drawn inline in the nav's own colour, at the nav icons' 16px size.
    $inline = [
        'nav-back' => '<circle cx="8" cy="8" r="7" fill="currentColor" opacity=".92"/><path d="M9.5 4.8 6.2 8l3.3 3.2M6.5 8h5" stroke="#121927" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-home' => '<path d="m2 7 6-5 6 5v6.3a.7.7 0 0 1-.7.7H9.8V9.8H6.2V14H2.7a.7.7 0 0 1-.7-.7V7Z" fill="currentColor"/><path d="m1.3 7.4 6.25-5.2a.7.7 0 0 1 .9 0l6.25 5.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-leads' => '<path d="m6.65 9.35 2.7-2.7M5.1 11.95l-1.05 1.05a2.15 2.15 0 0 1-3.05-3.04l2.3-2.3a2.15 2.15 0 0 1 3.05 0M10.9 4.05l1.05-1.05A2.15 2.15 0 1 1 15 6.04l-2.3 2.3a2.15 2.15 0 0 1-3.05 0" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-affiliates' => '<circle cx="6" cy="5" r="2.2" fill="currentColor"/><circle cx="11.8" cy="5.8" r="1.7" fill="currentColor" opacity=".8"/><path d="M1.8 13.5c.25-2.65 1.65-4 4.2-4s3.95 1.35 4.2 4H1.8Zm7.5-3.5c2.8-.45 4.45.75 4.9 3.5h-3.05A6.1 6.1 0 0 0 9.3 10Z" fill="currentColor"/>',
        'nav-ftd' => '<path d="M2 8.2 13.8 2 10 14 7.7 9.8 2 8.2Z" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/><path d="m7.7 9.8 2.5-2.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>',
        'nav-attribution' => '<path d="M4 11.8h8l-1.2-1.7V7a2.8 2.8 0 0 0-5.6 0v3.1L4 11.8Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/><path d="M6.7 13.3a1.45 1.45 0 0 0 2.6 0" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>',
        'nav-agents' => '<path d="M11.2 13.8v-1.2a3 3 0 0 0-3-3H4.4a3 3 0 0 0-3 3v1.2M6.3 6.8a2.7 2.7 0 1 0 0-5.4 2.7 2.7 0 0 0 0 5.4ZM11 2a2.7 2.7 0 0 1 0 5.2M14.6 13.8v-1.2a3 3 0 0 0-2.1-2.85" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-managers' => '<path d="M8 1.3c1.75 1.45 3.85 2.3 6 2.3v4.3c0 3.35-2.35 5.05-5.55 6.2a1.35 1.35 0 0 1-.9 0C4.35 12.95 2 11.25 2 7.9V3.6c2.15 0 4.25-.85 6-2.3Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>',
        'nav-open' => '<path d="M2 12.5 6 8.5l2.5 2.5L14 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.5 5.5H14V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-trades' => '<path d="M4 2.5v11M8 4v9.5M12 6.5v7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><rect x="2.75" y="5" width="2.5" height="5" rx=".6" fill="currentColor"/><rect x="6.75" y="6.5" width="2.5" height="4" rx=".6" fill="currentColor"/><rect x="10.75" y="8" width="2.5" height="3.5" rx=".6" fill="currentColor"/>',
        'nav-finances' => '<path d="M1.5 5.2h13v7.2h-13V5.2Zm2-2h9v2h-9v-2Z" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/><circle cx="8" cy="8.8" r="1.7" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 7.2v3.2M12.5 7.2v3.2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>',
        'nav-calendar' => '<rect x="2" y="3.3" width="12" height="10.7" rx="1.5" stroke="currentColor" stroke-width="1.35"/><path d="M5 1.7v3.1M11 1.7v3.1M2 6.5h12" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/><path d="M5 9h.01M8 9h.01M11 9h.01M5 11.5h.01M8 11.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'nav-splitter' => '<path d="M2 4h3.2c2.3 0 2.25 8 5.1 8H14M11.5 9.5 14 12l-2.5 2.5M2 12h3.2c1.2 0 1.75-2.1 2.35-4.15C8.15 5.8 8.8 4 10.3 4H14M11.5 1.5 14 4l-2.5 2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-upload' => '<path d="M4.2 12.8H3.4a2.4 2.4 0 0 1-.2-4.8A4.8 4.8 0 0 1 12.3 6a3.2 3.2 0 0 1 .3 6.4h-.8M8 13.8V7.2M5.7 9.5 8 7.2l2.3 2.3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
        'nav-assets' => '<text x="8" y="11.7" text-anchor="middle" fill="currentColor" font-family="Segoe UI,Arial,sans-serif" font-size="10" font-weight="700">W</text>',
        'nav-markups' => '<path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/><circle cx="5" cy="4" r="1.5" fill="#121927" stroke="currentColor" stroke-width="1.2"/><circle cx="10.5" cy="8" r="1.5" fill="#121927" stroke="currentColor" stroke-width="1.2"/><circle cx="7" cy="12" r="1.5" fill="#121927" stroke="currentColor" stroke-width="1.2"/>',
        'nav-settings' => '<circle cx="8" cy="8" r="2.2" stroke="currentColor" stroke-width="1.4"/><path d="M8 1.5v1.8M8 12.7v1.8M1.5 8h1.8M12.7 8h1.8M3.4 3.4l1.3 1.3M11.3 11.3l1.3 1.3M3.4 12.6l1.3-1.3M11.3 4.7l1.3-1.3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
        'nav-report' => '<circle cx="8" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.15"/>',
        // Google's own mark, in its own colours -- the one icon here that is not
        // currentColor, because a monochrome Drive triangle reads as nothing. Drawn at
        // its native 87.3x78 and scaled into the 16x16 box the nav uses.
        'nav-drive' => '<g transform="translate(0,0.86) scale(0.1833)"><path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/><path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0-1.2 4.5h27.5z" fill="#00ac47"/><path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/><path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/><path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/><path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/></g>',
    ];
    if (! isset($inline[$name])) {
        [$x,$y,$w,$h] = $icons[$name];
    }
    // Crisp replacements for crops that were soft or carried patches of the sheet (user request,
    // 10 Oct 2026). Line icons keep the crop's box and take the surrounding text colour; the KPI badges
    // are solid circles with a white icon; the empty-state art is drawn in the same blue at its size.
    $line = [
        'menu' => '<path d="M4 6.5h16M4 12h16M4 17.5h16"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>', 'nav-down' => '<path d="m6 9 6 6 6-6"/>', 'nav-up' => '<path d="m6 15 6-6 6 6"/>',
        'collapse' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8.5 12h7"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
        'sort' => '<path d="M8 4v16M4.5 16.5 8 20l3.5-3.5M16 20V4M12.5 7.5 16 4l3.5 3.5"/>',
    ];
    $inline['nav-assets'] = '<path d="M8 1.8 14.5 5 8 8.2 1.5 5 8 1.8Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/><path d="m1.5 8 6.5 3.2L14.5 8M1.5 11l6.5 3.2 6.5-3.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>';
    $hash = '<path d="M9.5 4 7.5 20M16.5 4l-2 16M4.5 9h15M3.5 15h15"/>';
    $badges = [
        'registration-badge' => ['#0d9488', '<circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c.5-3.6 2.9-5.6 6.5-5.6 1.6 0 3 .4 4 1.2M18 13v7M14.5 16.5h7"/>'],
        'deposit-badge' => ['#2563eb', '<path d="M12 4v16M16 8c-.5-1.4-2-2.3-4-2.3-2.3 0-4 1.1-4 2.8 0 4 8 2 8 6 0 1.7-1.7 2.9-4 2.9-2.1 0-3.7-.9-4.2-2.4"/>'],
        'deposit-count-badge' => ['#2563eb', $hash],
        'withdrawal-badge' => ['#db2777', '<rect x="3" y="7" width="18" height="12" rx="2"/><circle cx="12" cy="13" r="2.4"/><path d="M12 2.5v3M10 4.2l2-1.7 2 1.7"/>'],
        'withdrawal-count-badge' => ['#db2777', $hash],
    ];
    $empty = [
        'empty-performance' => '<rect x="9" y="22" width="10" height="18" rx="3"/><rect x="23" y="8" width="10" height="32" rx="3"/><rect x="37" y="16" width="10" height="24" rx="3"/>',
        'empty-new-leads' => '<circle cx="21" cy="14" r="7"/><path d="M8 38c1-7.4 5.8-11.4 13-11.4S33 30.6 34 38H8Z"/><circle cx="38" cy="16" r="5.5"/><path d="M36 26.8c5.8-.6 9.6 2.7 10.6 11.2H38"/>',
        'empty-affiliates' => '<circle cx="32" cy="12" r="7"/><path d="M19 40c1-7.4 5.8-11.4 13-11.4S44 32.6 45 40H19Z"/><circle cx="14" cy="17" r="5"/><path d="M3 40c.8-6 4.3-9 9.5-9 2 0 3.7.4 5 1.3"/><circle cx="50" cy="17" r="5"/><path d="M61 40c-.8-6-4.3-9-9.5-9-2 0-3.7.4-5 1.3"/>',
        'empty-comments' => '<path d="M29 5C15.7 5 5 13.9 5 25c0 5.2 2.3 9.9 6.2 13.4L9 50l11.3-5.7c2.7.8 5.6 1.2 8.7 1.2 13.3 0 24-8.9 24-20S42.3 5 29 5Z"/><circle cx="19" cy="25" r="1.6" fill="currentColor"/><circle cx="29" cy="25" r="1.6" fill="currentColor"/><circle cx="39" cy="25" r="1.6" fill="currentColor"/>',
    ];
@endphp
@if(isset($line[$name]))
<svg aria-hidden="true" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->class(['leadmax-reference-icon', 'leadmax-line-icon']) }} style="flex:none;vertical-align:middle">{!! $line[$name] !!}</svg>
@elseif(isset($badges[$name]))
<svg aria-hidden="true" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 28 28" {{ $attributes->class(['leadmax-reference-icon', 'leadmax-kpi-badge']) }}><circle cx="14" cy="14" r="13" fill="{{ $badges[$name][0] }}"/><g transform="translate(6.5 6.5) scale(.625)" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">{!! $badges[$name][1] !!}</g></svg>
@elseif(isset($empty[$name]))
<svg aria-hidden="true" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $name === 'empty-affiliates' ? '64 50' : ($name === 'empty-comments' ? '58 55' : '56 44') }}" fill="none" stroke="#3b82f6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->class(['leadmax-inline-icon', 'leadmax-empty-svg']) }} style="flex:none;color:#3b82f6">{!! $empty[$name] !!}</svg>
@elseif(isset($inline[$name]))
<svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" {{ $attributes->class(['leadmax-inline-icon']) }} style="flex:none;vertical-align:middle">{!! $inline[$name] !!}</svg>
@else
<span aria-hidden="true" {{ $attributes->class(['leadmax-reference-icon', 'leadmax-empty-art' => str_starts_with($name, 'empty-'), 'leadmax-nav-art' => str_starts_with($name, 'nav-')]) }} style="--ref-x:-{{ $x }}px;--ref-y:-{{ $y }}px;width:{{ $w }}px;height:{{ $h }}px"></span>
@endif
