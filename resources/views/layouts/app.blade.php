<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ \App\Support\SiteSettings::brandName() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --paper:#FBFAF7; --paper-2:#F4F1E9; --paper-3:#EDE9DE;
            --ink:#16140F; --ink-2:#4C473C; --ink-3:#7E7869;
            --rule:#E1DBCD; --rule-2:#CFC8B6;
            --forest:#1C4A3B; --forest-2:#2A6450; --forest-wash:#E9F0EC;
            --amber:#8A5A16; --amber-wash:#F7EEDC;
            --danger:#8C2F1B; --danger-wash:#FBEDEA;
            --radius:10px; --radius-sm:6px;
            --serif:"Instrument Serif",Georgia,serif;
            --sans:"Instrument Sans",-apple-system,"Segoe UI",Roboto,sans-serif;
        }
        *,*::before,*::after{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--sans);font-size:16px;line-height:1.55;-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}
        :focus-visible{outline:2px solid var(--forest);outline-offset:3px;border-radius:3px}
        .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

        .topbar{border-bottom:1px solid var(--rule);background:#fff;position:sticky;top:0;z-index:20}
        .topbar-inner{max-width:1200px;margin:0 auto;padding:0 clamp(16px,4vw,32px);min-height:64px;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap}
        .brand{display:flex;align-items:center;gap:.55rem;font-family:var(--serif);font-size:1.2rem;flex:none}
        .brand-mark{width:24px;height:24px;border-radius:5px;background:var(--forest);display:grid;place-items:center;color:#fff;font-family:var(--sans);font-size:.68rem;font-weight:600;flex:none}
        .brand-logo{height:24px;width:auto}
        .topnav{display:flex;gap:1.25rem;margin-left:auto;align-items:center;font-size:.875rem;flex-wrap:wrap}
        .topnav a{color:var(--ink-2)}
        .topnav a:hover{color:var(--ink)}

        .wrap{max-width:1200px;margin:0 auto;padding:clamp(24px,4vw,44px) clamp(16px,4vw,32px) 4rem}
        h1{font-family:var(--serif);font-weight:400;font-size:clamp(1.6rem,3vw,2.1rem);line-height:1.1;letter-spacing:-.02em;margin:0 0 .35rem}
        .sub{color:var(--ink-3);font-size:.9rem;margin:0 0 1.75rem}

        .card{background:#fff;border:1px solid var(--rule);border-radius:14px;padding:1.4rem;box-shadow:0 1px 3px rgba(22,20,15,.04)}
        .card + .card{margin-top:1rem}

        /* Page header: title block plus an action. Wraps instead of squeezing. */
        .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .page-head h1{margin-bottom:.25rem}
        .page-head .sub{margin-bottom:0}

        /*
         * Two-column content + sidebar.
         *
         * The old markup hard-coded `grid-template-columns: minmax(0,1fr) 280px`
         * inline, so at 375px the 280px sidebar kept its width and the content
         * column collapsed to 38px — the reason job pages were unreadable on a
         * phone. A named class can adapt; an inline style cannot.
         */
        .split{display:grid;grid-template-columns:minmax(0,1fr);gap:1.25rem;align-items:start}
        .split > *{min-width:0}

        /* Even two-up form rows. */
        .duo{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem}

        .alert{padding:.75rem .9rem;border-radius:var(--radius-sm);font-size:.865rem;margin-bottom:1.1rem;line-height:1.5}
        .alert-error{background:var(--danger-wash);color:var(--danger)}
        .alert-ok{background:var(--forest-wash);color:var(--forest)}
        .alert-note{background:var(--amber-wash);color:var(--amber)}
        .alert ul{margin:.3rem 0 0;padding-left:1.1rem}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.4em;padding:.55rem .9rem;border-radius:var(--radius-sm);font:inherit;font-size:.855rem;font-weight:500;border:1px solid transparent;cursor:pointer;background:var(--forest);color:#fff;transition:background .15s ease;white-space:nowrap}
        .btn:hover{background:var(--forest-2)}
        .btn-secondary{background:none;border-color:var(--rule-2);color:var(--ink)}
        .btn-secondary:hover{background:var(--paper-2);border-color:var(--ink-3)}
        .btn-danger{background:var(--danger)}
        .btn-danger:hover{background:#6f2515}
        .btn-sm{padding:.4rem .65rem;font-size:.8rem}

        .badge{display:inline-flex;align-items:center;gap:.32em;padding:.2rem .55rem;border-radius:100px;font-size:.72rem;font-weight:600;white-space:nowrap}
        .badge-approved{background:var(--forest-wash);color:var(--forest)}
        .badge-pending{background:var(--amber-wash);color:var(--amber)}
        .badge-suspended,.badge-rejected{background:var(--danger-wash);color:var(--danger)}
        .badge-deleted,.badge-draft{background:var(--paper-3);color:var(--ink-3)}
        .badge-freelancer{background:var(--paper-2);color:var(--ink-2)}
        .badge-poster{background:var(--paper-2);color:var(--ink-2)}
        .badge-admin{background:var(--ink);color:var(--paper)}

        table{width:100%;border-collapse:collapse;font-size:.875rem}
        th{text-align:left;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);font-weight:600;padding:.6rem .5rem;border-bottom:1px solid var(--rule)}
        td{padding:.7rem .5rem;border-bottom:1px solid var(--rule);vertical-align:middle}
        tr:last-child td{border-bottom:0}
        .actions{display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end}

        .filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
        .filters select,.filters input{padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:var(--radius-sm);font:inherit;font-size:.85rem;background:#fff;color:var(--ink);min-width:0}

        /* Definition list used for money/date facts. */
        .facts{margin:0;display:grid;gap:.9rem;font-size:.87rem}
        .facts dt{font-size:.72rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);font-weight:600}
        .facts dd{margin:.2rem 0 0}
        /* Label/value rows: side by side where there is room, stacked on a phone. */
        .fact-row{display:grid;grid-template-columns:minmax(0,1fr);gap:.15rem}
        @media (min-width:520px){
            .fact-row{grid-template-columns:140px minmax(0,1fr);gap:1rem;align-items:baseline}
            .fact-row dt{text-transform:none;letter-spacing:0;font-size:.8rem}
            .fact-row dd{margin:0}
        }

        /* Card list: a table's mobile form. */
        .cardlist{display:grid;gap:.75rem}

        /*
         * Bottom navigation (mobile only).
         *
         * Hidden above 700px: a phone-style tab bar on a 1440px screen would be
         * an affectation, and the top nav already works there. It appears at the
         * same breakpoint the top nav starts scrolling, so there is always one
         * obvious way to move around.
         */
        .bottomnav{display:none}

        @media (max-width:700px){
            .bottomnav{
                display:grid;
                grid-auto-flow:column;
                grid-auto-columns:1fr;
                position:fixed;
                left:0;right:0;bottom:0;
                z-index:40;
                background:rgba(255,255,255,.94);
                backdrop-filter:blur(12px);
                -webkit-backdrop-filter:blur(12px);
                border-top:1px solid var(--rule);
                /* Clear the iPhone home indicator. */
                padding-bottom:env(safe-area-inset-bottom, 0px);
            }
            .bottomnav a{
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                gap:.15rem;
                padding:.5rem .25rem .55rem;
                font-size:.66rem;
                font-weight:500;
                letter-spacing:.01em;
                color:var(--ink-3);
                text-decoration:none;
                min-height:52px;
                position:relative;
                -webkit-tap-highlight-color:transparent;
            }
            .bottomnav a svg{width:21px;height:21px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
            .bottomnav a.is-active{color:var(--forest)}
            .bottomnav a.is-active::before{
                content:"";
                position:absolute;
                top:0;left:50%;
                transform:translateX(-50%);
                width:26px;height:2px;
                border-radius:0 0 2px 2px;
                background:var(--forest);
            }
            /* Keep the last row of content clear of the bar. */
            body{padding-bottom:calc(64px + env(safe-area-inset-bottom, 0px))}
        }

        /*
         * ------------------------------------------------------------------
         * Tables become cards below 760px.
         * ------------------------------------------------------------------
         * A horizontally scrolling table technically "works" but buries the
         * money column off-screen, which is the one value the page exists to
         * show. Each td carries data-label so the card still names its fields.
         */
        @media (max-width:760px){
            table.responsive thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
            table.responsive,table.responsive tbody,table.responsive tr,table.responsive td{display:block;width:100%}
            table.responsive tr{border:1px solid var(--rule);border-radius:12px;padding:.9rem;background:#fff;margin-bottom:.7rem}
            table.responsive tr:last-child{margin-bottom:0}
            table.responsive td{border:0;padding:.3rem 0;display:flex;justify-content:space-between;gap:1rem;align-items:baseline;text-align:right}
            table.responsive td::before{
                content:attr(data-label);
                flex:none;
                font-size:.7rem;
                letter-spacing:.07em;
                text-transform:uppercase;
                color:var(--ink-3);
                font-weight:600;
                text-align:left;
            }
            /* The primary cell reads as a heading, not a labelled row. */
            table.responsive td.cell-primary{display:block;text-align:left;padding-bottom:.6rem;margin-bottom:.2rem;border-bottom:1px solid var(--rule)}
            table.responsive td.cell-primary::before{content:none}
            table.responsive td.cell-actions{display:block;text-align:left;padding-top:.6rem}
            table.responsive td.cell-actions::before{content:none}
            table.responsive td.cell-actions .actions{justify-content:flex-start}
            /* Full-width table container must not keep its own border. */
            .table-shell{margin-top:1rem}
        }

        /* ------------------------------------------------------------------ *
         * Mobile: <= 700px
         * ------------------------------------------------------------------ */
        @media (max-width:700px){
            .topbar-inner{gap:.75rem;padding-top:.6rem;padding-bottom:.6rem;min-height:0}
            .brand{font-size:1.05rem}
            .topnav{
                width:100%;
                margin-left:0;
                gap:.15rem;
                font-size:.82rem;
                overflow-x:auto;
                -webkit-overflow-scrolling:touch;
                scrollbar-width:none;
                padding-bottom:.15rem;
            }
            .topnav::-webkit-scrollbar{display:none}
            .topnav a{padding:.4rem .5rem;border-radius:6px;white-space:nowrap}
            .topnav a:hover{background:var(--paper-2)}
            .topnav form{margin-left:auto}
            .topnav .btn-sm{padding:.35rem .55rem}

            .wrap{padding-top:1.35rem}
            h1{font-size:1.45rem}
            .card{padding:1.1rem;border-radius:12px}
            .filters{gap:.4rem}
            .filters input,.filters select{width:100%;flex:1 1 100%}
            .filters .btn{flex:1 1 auto}
            .page-head{gap:.75rem}
            .actions{justify-content:flex-start}
        }

        /* ------------------------------------------------------------------ *
         * Wide: >= 860px gets the sidebar back.
         * ------------------------------------------------------------------ */
        @media (min-width:860px){
            .split{grid-template-columns:minmax(0,1fr) 300px;gap:1.5rem}
            .duo{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
        }

        /* Respect a user's reduced-motion preference. */
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important}
        }
    </style>
    {{-- Page-specific CSS. Kept out of the shared block so one page's layout
         tweaks cannot change how every other page behaves. --}}
    @stack('styles')
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ route('dashboard') }}">
            @if(\App\Support\LogoUploader::url())
                <img class="brand-logo" src="{{ \App\Support\LogoUploader::url() }}" alt="{{ \App\Support\SiteSettings::brandName() }}">
            @else
                <span class="brand-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
            @endif
            {{ \App\Support\SiteSettings::brandName() }}
        </a>

        <nav class="topnav">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}">Accounts</a>
                @endif
                @if(auth()->user()->isFreelancer())
                    <a href="{{ route('portfolio.index') }}">Portfolio</a>
                    <a href="{{ route('profile.edit') }}">Profile</a>
                @endif
                @if(auth()->user()->isPoster())
                    <a href="{{ route('jobs.manage.index') }}">My jobs</a>
                @endif
                <a href="{{ route('jobs.index') }}">Browse jobs</a>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">Sign out</button>
                </form>
            @endauth
        </nav>
    </div>
</header>

<main class="wrap">
    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@auth
    {{--
        Bottom navigation.

        Only rendered for signed-in users, because every destination except
        "Jobs" requires an account and a signed-out visitor tapping a link that
        bounces to the login page feels like a dead end.

        `aria-current="page"` is what tells a screen reader which tab is active;
        the underline alone is a visual-only signal.
    --}}
    @php
        $navRole = auth()->user()->type->value;
        $isActive = fn (string $pattern): string => request()->routeIs($pattern) ? 'is-active' : '';
    @endphp

    <nav class="bottomnav" aria-label="Primary">
        <a href="{{ route('dashboard') }}" class="{{ $isActive('dashboard') }}"
           @if (request()->routeIs('dashboard')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/></svg>
            <span>Home</span>
        </a>

        <a href="{{ route('jobs.index') }}" class="{{ $isActive('jobs.*') }}"
           @if (request()->routeIs('jobs.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/><path d="M3 12h18"/></svg>
            <span>Jobs</span>
        </a>

        @if ($navRole === 'poster')
            <a href="{{ route('jobs.manage.index') }}" class="{{ $isActive('jobs.manage.*') }}"
               @if (request()->routeIs('jobs.manage.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                <span>Post</span>
            </a>
        @endif

        @if ($navRole === 'freelancer')
            <a href="{{ route('portfolio.index') }}" class="{{ $isActive('portfolio.*') }}"
               @if (request()->routeIs('portfolio.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 15 5-4 4 3 3-2 6 4"/></svg>
                <span>Work</span>
            </a>

            <a href="{{ route('profile.edit') }}" class="{{ $isActive('profile.*') }}"
               @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="3.6"/><path d="M4.8 20a7.4 7.4 0 0 1 14.4 0"/></svg>
                <span>Profile</span>
            </a>
        @endif

        @if ($navRole === 'admin')
            <a href="{{ route('admin.users.index') }}" class="{{ $isActive('admin.*') }}"
               @if (request()->routeIs('admin.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M2.8 19a6.4 6.4 0 0 1 12.4 0"/><path d="M17 8.5h4M19 6.5v4"/></svg>
                <span>Accounts</span>
            </a>
        @endif
    </nav>
@endauth
</body>
</html>
