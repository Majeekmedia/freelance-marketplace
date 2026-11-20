@extends('layouts.app')

@section('title', $freelancer->name)

@section('content')
<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;align-items:start">
    <div>
        <h1>{{ $freelancer->name }}</h1>
        @if (! empty($public['headline']))
            <p class="sub" style="font-size:1rem;color:var(--ink-2)">{{ $public['headline'] }}</p>
        @endif

        @if (! empty($public['bio']))
            <div class="card">
                <p style="margin:0;font-size:.9rem;color:var(--ink-2);white-space:pre-line">{{ $public['bio'] }}</p>
            </div>
        @endif

        @if ($portfolio->isNotEmpty())
            <h2 style="font-family:var(--serif);font-weight:400;font-size:1.25rem;margin:2rem 0 .9rem">Portfolio</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem">
                @foreach ($portfolio as $item)
                    <div class="card" style="padding:0;overflow:hidden">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="" style="width:100%;height:130px;object-fit:cover;display:block">
                        @endif
                        <div style="padding:.9rem">
                            <p style="margin:0;font-weight:600;font-size:.9rem">{{ $item['title'] }}</p>
                            @if ($item['description'])
                                <p style="margin:.35rem 0 0;font-size:.82rem;color:var(--ink-2)">{{ $item['description'] }}</p>
                            @endif
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" rel="nofollow noopener noreferrer" target="_blank"
                                   style="display:inline-block;margin-top:.55rem;font-size:.8rem;color:var(--forest);text-decoration:underline">
                                    View work
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <aside>
        <div class="card">
            @if (! empty($public['avatar_url']))
                <img src="{{ $public['avatar_url'] }}" alt=""
                     style="width:72px;height:72px;border-radius:50%;object-fit:cover;display:block;margin-bottom:.9rem">
            @endif

            @if (! empty($public['hourly_rate']))
                <p style="margin:0 0 .35rem;font-family:var(--serif);font-size:1.5rem;line-height:1">{{ $public['hourly_rate'] }}<span style="font-size:.7rem;font-family:var(--sans);color:var(--ink-3)"> /hr</span></p>
            @endif

            @if (! empty($public['location']))
                <p style="margin:.5rem 0 0;font-size:.83rem;color:var(--ink-3)">{{ $public['location'] }}</p>
            @endif

            @if (! empty($public['skills']))
                <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.9rem">
                    @foreach ($public['skills'] as $skill)
                        <span style="font-size:.72rem;padding:.18rem .5rem;border-radius:4px;background:var(--paper-2);color:var(--ink-2);font-weight:500">{{ $skill }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <p style="font-size:.78rem;color:var(--ink-3);margin-top:.9rem;line-height:1.5">
            Contact details are exchanged after a job is funded and a conversation is opened.
        </p>
    </aside>
</div>
@endsection
