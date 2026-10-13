@props(['name', 'width' => 16, 'height' => 16, 'lightName' => null])
<img src="{{ asset('images/leadmax/'.$name.'.svg') }}" alt="" width="{{ $width }}" height="{{ $height }}" style="width:{{ $width }}px;height:{{ $height }}px" {{ $attributes->class(['leadmax-icon', 'leadmax-dark-only' => $lightName]) }} />
@if($lightName)
    <img src="{{ asset('images/leadmax/'.$lightName.'.svg') }}" alt="" width="{{ $width }}" height="{{ $height }}" style="width:{{ $width }}px;height:{{ $height }}px" {{ $attributes->class(['leadmax-icon', 'leadmax-light-only']) }} />
@endif
