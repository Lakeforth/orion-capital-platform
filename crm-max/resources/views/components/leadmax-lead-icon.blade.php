@props(['name'])
@php
    $map = [
        'add'=>[460,535,122,57,58], 'total'=>[459,255,182,52,52], 'new'=>[459,519,182,52,52], 'contacted'=>[459,768,182,52,52],
        'qualified'=>[459,1024,182,52,52], 'converted'=>[459,1268,182,52,52], 'disqualified'=>[459,1535,182,52,52],
        'up'=>[459,347,244,18,22], 'down'=>[459,1634,244,18,22], 'plus'=>[459,1524,115,18,18],
        'export'=>[459,1646,115,18,18], 'filter'=>[459,1747,114,23,23], 'search'=>[459,1295,314,22,22],
        'dots'=>[459,1738,409,13,20], 'eye'=>[459,1694,407,24,22], 'phone'=>[459,560,407,18,20], 'flag-israel'=>[459,932,407,26,20],
        'sort'=>[459,351,368,16,15], 'info'=>[459,312,69,19,19],
        'edit'=>[461,1526,480,25,24], 'comment'=>[461,1526,515,25,25], 'assign'=>[461,1526,550,25,26],
        'status'=>[461,1526,584,25,26], 'delete'=>[461,1526,634,25,27],
        'created'=>[463,527,395,54,54], 'phone-detail'=>[462,523,370,27,27], 'shield'=>[462,1067,504,26,26], 'avatar'=>[462,507,161,64,64], 'overview'=>[462,516,253,23,23], 'activity'=>[462,683,252,26,26], 'comments'=>[462,850,252,25,26],
        'contact'=>[462,520,322,25,25], 'details'=>[462,520,457,25,25], 'mail'=>[462,796,369,25,25],
        'location'=>[462,1067,370,25,25], 'team'=>[462,523,505,25,27], 'globe'=>[462,796,505,25,26],
        'calendar'=>[462,523,559,25,26], 'owner'=>[462,797,561,25,26], 'clock'=>[462,1067,562,25,26],
        'close'=>[462,1256,166,23,23], 'empty-comment'=>[464,865,390,62,62], 'attach'=>[464,529,691,26,23], 'send'=>[464,1127,690,27,25],
        'status-large'=>[467,601,82,63,63], 'status-new'=>[467,615,314,46,42], 'status-contacted'=>[467,615,374,46,42], 'status-qualified'=>[467,615,435,46,42], 'status-converted'=>[467,615,495,46,42], 'status-disqualified'=>[467,615,554,46,42], 'success'=>[466,1122,84,34,34],
        'owner-large'=>[465,578,160,70,71],
    ];
    [$sheet,$x,$y,$w,$h] = $map[$name];
    // Crisp line icons drawn inline (user request, 10 Oct 2026): the reference-sheet crops of these
    // were soft and carried patches of the sheet's background. Same box size as the crop, in the
    // surrounding text colour (CSS .lm-lead-svg); the flags and the large illustrated tiles keep the sheet.
    $users = '<circle cx="9" cy="8" r="3.5"/><path d="M2.8 20c.5-3.6 2.8-5.6 6.2-5.6s5.7 2 6.2 5.6M16 4.6a3.5 3.5 0 0 1 0 6.8M18.2 14.6c1.8.6 2.8 2.4 3 5.4"/>';
    $comment = '<path d="M4 5h16v11H9.5L4 20V5Z"/><path d="M8 9.2h8M8 12.4h5"/>';
    $phone = '<path d="M20.5 16.4v2.7a1.9 1.9 0 0 1-2.1 1.9A17.6 17.6 0 0 1 3 5.6 1.9 1.9 0 0 1 4.9 3.5h2.7a1.9 1.9 0 0 1 1.9 1.6c.1.9.4 1.8.7 2.6a1.9 1.9 0 0 1-.4 2L8.6 10.9a15 15 0 0 0 4.5 4.5l1.2-1.2a1.9 1.9 0 0 1 2-.4c.8.3 1.7.6 2.6.7a1.9 1.9 0 0 1 1.6 1.9Z"/>';
    $svg = [
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'export' => '<path d="M12 4v11M7.5 10.5 12 15l4.5-4.5M5 19.5h14"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>',
        'filter' => '<path d="M4 5h16l-6.2 7.4V18l-3.6 1.9v-7.5L4 5Z"/>',
        'dots' => '<circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.4" fill="currentColor" stroke="none"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'phone' => $phone, 'phone-detail' => $phone,
        'mail' => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'sort' => '<path d="M7 4v15.5M3.5 16 7 19.5 10.5 16M14 6h7M14 11h5M14 16h3"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5"/><circle cx="12" cy="7.8" r="1.1" fill="currentColor" stroke="none"/>',
        'up' => '<path d="M12 19.5V5M6 11l6-6 6 6"/>', 'down' => '<path d="M12 4.5V19M6 13l6 6 6-6"/>',
        'edit' => '<path d="M16.4 3.6a2.2 2.2 0 0 1 3.1 3.1L8 18.2l-4.2 1 1-4.2L16.4 3.6Z"/><path d="m14.3 5.7 3.1 3.1"/>',
        'comment' => $comment, 'comments' => $comment,
        'assign' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.8 20c.5-3.6 2.8-5.6 6.2-5.6s5.7 2 6.2 5.6M19 7.5v6M16 10.5h6"/>',
        'status' => '<path d="M20 11A8 8 0 0 0 5.4 6.5L4 8M4 3.8V8h4.2M4 13a8 8 0 0 0 14.6 4.5L20 16M20 20.2V16h-4.2"/>',
        'delete' => '<path d="M4 7h16M9.5 7V4.5h5V7M6 7l1 13h10l1-13M10 11v5.5M14 11v5.5"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'overview' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5"/><circle cx="12" cy="7.8" r="1.1" fill="currentColor" stroke="none"/>',
        'activity' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.5-6L3.5 8.5M3.5 4v4.5H8"/><path d="M12 7.5V12l3 2"/>',
        'shield' => '<path d="M12 3 19.5 6v5.5c0 4.6-3.2 7.9-7.5 9.5-4.3-1.6-7.5-4.9-7.5-9.5V6L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
        'contact' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="2.6"/><path d="M7.5 17c.6-2 2.3-3.1 4.5-3.1s3.9 1.1 4.5 3.1"/>',
        'details' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v2.5H9zM8.5 10h7M8.5 13.5h7M8.5 17h4"/>',
        'location' => '<path d="M12 21s7-6.1 7-11.4a7 7 0 0 0-14 0C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.6" r="2.5"/>',
        'team' => $users,
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3Z"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
        'owner' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c.8-4 3.7-6 7.5-6s6.7 2 7.5 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'attach' => '<path d="m20.5 11.6-8.3 8.3a5 5 0 0 1-7.1-7.1l8.3-8.3a3.4 3.4 0 0 1 4.8 4.8l-8.3 8.3a1.7 1.7 0 0 1-2.4-2.4l7.6-7.6"/>',
    ];
@endphp
@if(isset($svg[$name]))
<svg aria-hidden="true" width="{{ $w }}" height="{{ $h }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->class('lm-lead-icon lm-lead-svg lm-icon-'.$name) }}>{!! $svg[$name] !!}</svg>
@else
<span aria-hidden="true" {{ $attributes->class('lm-lead-icon lm-icon-'.$name) }} style="background-image:url('{{ asset('images/leadmax/leads/'.$sheet.'.png') }}');background-position:-{{ $x }}px -{{ $y }}px;width:{{ $w }}px;height:{{ $h }}px;background-size:{{ $sheet===465?1791:1792 }}px 878px"></span>
@endif
