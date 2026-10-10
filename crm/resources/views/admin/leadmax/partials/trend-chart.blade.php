{{--
    Line chart for LeadMaxController::trendWindow() series. $series: [[tone (blue|green|amber), [date => value]], ...] sharing one
    y scale; $label: accessible summary. Plot x 8..310, y 10 (max) .. 70 (zero) in a 320 x 80 box, stretched to the card width.
--}}
@php
    $all = collect($series)->flatMap(fn ($s) => array_values($s[1]));
    $max = max(1, $all->max() ?? 0);
    $dates = array_keys($series[0][1] ?? []);
    $n = count($dates);
    $x = fn ($i) => round($n > 1 ? 8 + $i * 302 / ($n - 1) : 159, 1);
    $points = fn (array $values) => collect(array_values($values))->map(fn ($v, $i) => $x($i).','.round(70 - $v / $max * 60, 1))->implode(' ');
    $labels = collect(range(0, 4))->map(fn ($k) => $dates[(int) round($k * max(0, $n - 1) / 4)] ?? null)->filter()->unique()
        ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M d'))->values();
@endphp
<svg viewBox="0 0 320 80" preserveAspectRatio="none" role="img" aria-label="{{ $label }}" @isset($height) style="width:100%;height:{{ $height }}px" @endisset>
    <g class="chart-grid"><path vector-effect="non-scaling-stroke" d="M8 10H310M8 30H310M8 50H310M8 70H310"/></g>
    @foreach($series as [$tone, $values])
        @php $line = $points($values); @endphp
        <path class="chart-area {{ $tone }}-area" d="M{{ str_replace(' ', ' L', $line) }} L310 70 L8 70Z"/>
        <polyline class="chart-line {{ $tone }}-line" vector-effect="non-scaling-stroke" points="{{ $line }}"/>
    @endforeach
</svg>
<div class="chart-labels">@foreach($labels as $text)<span>{{ $text }}</span>@endforeach</div>
