{{-- Site brand: logo when uploaded, otherwise a text wordmark. --}}
<a {{ $attributes->merge(['class' => 'brand', 'href' => url('/')]) }}>
    @if($logoUrl)
        <img src="{{ $logoUrl }}"
             alt="{{ $brandName }}"
             class="brand-logo"
             height="26">
    @else
        <span class="brand-mark" aria-hidden="true">{{ $initials }}</span>
        <span class="brand-text">{{ $brandName }}</span>
    @endif
</a>
