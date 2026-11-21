<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--
        Admin pages are not indexed and not shareable as a "someone is an admin
        here" signal. robots is advisory but it is the cheap half of the message.
    --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — {{ \App\Support\SiteSettings::brandName() }}</title>
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

            /* Console chrome. Deliberately darker than the customer app so an
               operator can tell at a glance which surface they are on — the
               expensive mistake in an ops tool is acting on the wrong screen. */
            --shell:#141310;
            --shell-2:#1F1D18;
            --shell-3:#2A271F;
            --shell-ink:#F3EFE6;
            --shell-ink-2:#A8A092;
            --shell-accent:#7FB09C;

            --radius:10px; --radius-sm:6px;
            --serif:"Instrument Serif",Georgia,serif;
            --sans:"Instrument Sans",-apple-system,"Segoe UI",Roboto,sans-serif;
        }
        *,*::before,*::after{box-sizing:border-box}
        body{
            margin:0;background:var(--paper);color:var(--ink);
            font-family:var(--sans);font-size:16px;line-height:1.55;
            -webkit-font-smoothing:antialiased;
        }
        a{color:inherit;text-decoration:none}
        :focus-visible{outline:2px solid var(--forest);outline-offset:3px;border-radius:3px}
        .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

        /* ---------------------------------------------------------------- shell */
        .shell{display:flex;min-height:100vh}

        .sidebar{
            width:236px;flex:none;
            background:var(--shell);color:var(--shell-ink);
            display:flex;flex-direction:column;
            position:sticky;top:0;height:100vh;
        }
        .sidebar-brand{
            display:flex;align-items:center;gap:.55rem;
            padding:1.15rem 1.15rem 1.1rem;
            border-bottom:1px solid var(--shell-2);
            font-family:var(--serif);font-size:1.05rem;color:var(--shell-ink);
        }
        .sidebar-mark{
            width:26px;height:26px;border-radius:6px;flex:none;
            background:var(--shell-accent);color:var(--shell);
            display:grid;place-items:center;
            font-family:var(--sans);font-size:.62rem;font-weight:700;
            letter-spacing:.02em;
        }
        .sidebar-scope{
            display:block;font-family:var(--sans);font-size:.63rem;
            letter-spacing:.13em;text-transform:uppercase;
            color:var(--shell-ink-2);margin-top:.1rem;
        }

        .sidebar-nav{padding:.9rem .6rem;display:flex;flex-direction:column;gap:.15rem;overflow-y:auto;flex:1}
        .sidebar-group{
            font-size:.6rem;letter-spacing:.14em;text-transform:uppercase;
            color:#6B655A;padding:1rem .6rem .35rem;font-weight:600;
        }
        .sidebar-nav a{
            display:flex;align-items:center;gap:.6rem;
            padding:.5rem .6rem;border-radius:7px;
            font-size:.855rem;color:var(--shell-ink-2);
        }
        .sidebar-nav a:hover{background:var(--shell-2);color:var(--shell-ink)}
        .sidebar-nav a[aria-current="page"]{background:var(--shell-3);color:#fff}
        .sidebar-nav a[aria-current="page"] svg{color:var(--shell-accent)}
        .sidebar-nav svg{width:17px;height:17px;flex:none;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
        .nav-count{
            margin-left:auto;font-size:.68rem;font-weight:600;
            background:var(--amber);color:#241c08;
            padding:.02rem .38rem;border-radius:100px;
            font-variant-numeric:tabular-nums;
        }
        .nav-soon{
            margin-left:auto;font-size:.58rem;letter-spacing:.06em;
            text-transform:uppercase;color:#6B655A;font-weight:600;
        }

        .sidebar-foot{padding:.75rem .6rem 1rem;border-top:1px solid var(--shell-2)}
        .sidebar-foot form{margin:0}
        .sidebar-foot button{
            display:flex;align-items:center;gap:.6rem;width:100%;
            padding:.5rem .6rem;border-radius:7px;border:0;background:none;
            font:inherit;font-size:.855rem;color:var(--shell-ink-2);cursor:pointer;text-align:left;
        }
        .sidebar-foot button:hover{background:var(--shell-2);color:var(--shell-ink)}
        .sidebar-foot svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}

        /* ------------------------------------------------------------ main area */
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{
            display:flex;align-items:center;gap:1rem;
            padding:.75rem clamp(16px,3vw,32px);
            background:#fff;border-bottom:1px solid var(--rule);
            position:sticky;top:0;z-index:10;
        }
        .topbar-title{font-size:.82rem;color:var(--ink-3)}
        .topbar-title strong{color:var(--ink);font-weight:600}
        .topbar-actions{margin-left:auto;display:flex;align-items:center;gap:.6rem;font-size:.8rem}
        .who{color:var(--ink-2)}
        .who b{font-weight:600;color:var(--ink)}

        .wrap{padding:clamp(20px,3vw,34px) clamp(16px,3vw,32px) 4rem;max-width:1200px;width:100%}

        h1{font-family:var(--serif);font-weight:400;font-size:clamp(1.5rem,2.6vw,1.95rem);line-height:1.12;letter-spacing:-.02em;margin:0 0 .3rem}
        .sub{color:var(--ink-3);font-size:.875rem;margin:0 0 1.6rem}
        .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .page-head h1{margin-bottom:.25rem}
        .page-head .sub{margin-bottom:0}

        .card{background:#fff;border:1px solid var(--rule);border-radius:13px;padding:1.3rem;box-shadow:0 1px 3px rgba(22,20,15,.04)}
        .card + .card{margin-top:1rem}

        .alert{padding:.75rem .9rem;border-radius:var(--radius-sm);font-size:.86rem;margin-bottom:1.1rem;line-height:1.5}
        .alert-error{background:var(--danger-wash);color:var(--danger)}
        .alert-ok{background:var(--forest-wash);color:var(--forest)}
        .alert-note{background:var(--amber-wash);color:var(--amber)}
        .alert ul{margin:.3rem 0 0;padding-left:1.1rem}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.4em;padding:.55rem .9rem;border-radius:var(--radius-sm);font:inherit;font-size:.85rem;font-weight:500;border:1px solid transparent;cursor:pointer;background:var(--forest);color:#fff;white-space:nowrap}
        .btn:hover{background:var(--forest-2)}
        .btn-secondary{background:none;border-color:var(--rule-2);color:var(--ink)}
        .btn-secondary:hover{background:var(--paper-2);border-color:var(--ink-3)}
        .btn-danger{background:var(--danger)}
        .btn-danger:hover{background:#6f2515}
        .btn-sm{padding:.4rem .65rem;font-size:.79rem}

        .badge{display:inline-flex;align-items:center;gap:.32em;padding:.2rem .55rem;border-radius:100px;font-size:.7rem;font-weight:600;white-space:nowrap}
        .badge-approved{background:var(--forest-wash);color:var(--forest)}
        .badge-pending{background:var(--amber-wash);color:var(--amber)}
        .badge-suspended,.badge-rejected{background:var(--danger-wash);color:var(--danger)}
        .badge-deleted,.badge-draft{background:var(--paper-3);color:var(--ink-3)}
        .badge-freelancer,.badge-poster{background:var(--paper-2);color:var(--ink-2)}
        .badge-admin{background:var(--ink);color:var(--paper)}

        table{width:100%;border-collapse:collapse;font-size:.86rem}
        th{text-align:left;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);font-weight:600;padding:.6rem .5rem;border-bottom:1px solid var(--rule)}
        td{padding:.7rem .5rem;border-bottom:1px solid var(--rule);vertical-align:middle}
        tr:last-child td{border-bottom:0}
        .actions{display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end}

        .filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
        .filters select,.filters input{padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:var(--radius-sm);font:inherit;font-size:.84rem;background:#fff;color:var(--ink);min-width:0}

        .facts{margin:0;display:grid;gap:.9rem;font-size:.86rem}
        .facts dt{font-size:.68rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);font-weight:600}
        .facts dd{margin:.2rem 0 0}

        .split{display:grid;grid-template-columns:minmax(0,1fr);gap:1.25rem;align-items:start}
        .split > *{min-width:0}
        .duo{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem}
        .cardlist{display:grid;gap:.75rem}

        /*
         * Mobile: the sidebar becomes a bottom bar.
         *
         * The sidebar is not "hidden with a hamburger". An operator on a phone
         * needs one tap to the queue, not two. Destinations that do not fit get
         * their own row in a labelled overflow section on the desktop sidebar,
         * and the four most-used become the tabs.
         */
        .mobile-tabs{display:none}

        @media (max-width:860px){
            .shell{display:block}
            .sidebar{display:none}
            .wrap{padding-bottom:calc(84px + env(safe-area-inset-bottom,0px))}

            .mobile-tabs{
                display:grid;grid-auto-flow:column;grid-auto-columns:1fr;
                position:fixed;left:0;right:0;bottom:0;z-index:40;
                background:var(--shell);color:var(--shell-ink);
                border-top:1px solid var(--shell-2);
                padding-bottom:env(safe-area-inset-bottom,0px);
            }
            .mobile-tabs a,
            .mobile-tabs button{
                display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.15rem;
                padding:.5rem .2rem .55rem;min-height:54px;
                font:inherit;font-size:.63rem;font-weight:500;
                color:var(--shell-ink-2);background:none;border:0;cursor:pointer;
                position:relative;-webkit-tap-highlight-color:transparent;
            }
            .mobile-tabs a svg,.mobile-tabs button svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
            .mobile-tabs a[aria-current="page"]{color:#fff}
            .mobile-tabs a[aria-current="page"]::before{
                content:"";position:absolute;top:0;left:50%;transform:translateX(-50%);
                width:26px;height:2px;border-radius:0 0 2px 2px;background:var(--shell-accent);
            }
            .mobile-tabs a[aria-current="page"] svg{color:var(--shell-accent)}
            .mobile-tab-count{
                position:absolute;top:.35rem;right:calc(50% - 18px);
                min-width:15px;height:15px;border-radius:100px;
                background:var(--amber);color:#241c08;
                font-size:.58rem;font-weight:700;line-height:15px;text-align:center;
                padding:0 .22rem;
            }

            .topbar-title{display:none}
            .who{display:none}
        }

        @media (max-width:760px){
            table.responsive thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
            table.responsive,table.responsive tbody,table.responsive tr,table.responsive td{display:block;width:100%}
            table.responsive tr{border:1px solid var(--rule);border-radius:12px;padding:.9rem;background:#fff;margin-bottom:.7rem}
            table.responsive td{border:0;padding:.3rem 0;display:flex;justify-content:space-between;gap:1rem;align-items:baseline;text-align:right}
            table.responsive td::before{
                content:attr(data-label);flex:none;
                font-size:.68rem;letter-spacing:.07em;text-transform:uppercase;
                color:var(--ink-3);font-weight:600;text-align:left;
            }
            table.responsive td.cell-primary{display:block;text-align:left;padding-bottom:.6rem;margin-bottom:.2rem;border-bottom:1px solid var(--rule)}
            table.responsive td.cell-primary::before{content:none}
            table.responsive td.cell-actions{display:block;text-align:left;padding-top:.6rem}
            table.responsive td.cell-actions::before{content:none}
            table.responsive td.cell-actions .actions{justify-content:flex-start}
        }

        @media (max-width:700px){
            h1{font-size:1.4rem}
            .card{padding:1.05rem;border-radius:11px}
            .filters input,.filters select{width:100%;flex:1 1 100%}
            .actions{justify-content:flex-start}
        }

        @media (min-width:900px){
            .split{grid-template-columns:minmax(0,1fr) 320px;gap:1.5rem}
            .duo{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
        }

        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important}
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    /*
     * Navigation is defined once, here, and rendered into two shapes: the
     * desktop sidebar and the mobile tab bar. Defining it twice is how the two
     * drift apart and a destination ends up reachable on one surface only.
     */
    $pendingAccounts = \App\Models\User::pending()->count();
    $pendingDeposits = \App\Models\DepositRequest::pending()->count();

    $navSections = [
        'Operate' => [
            [
                'route' => 'admin.overview',
                'label' => 'Overview',
                'pattern' => 'admin.overview',
                'icon' => 'grid',
            ],
            [
                'route' => 'admin.deposits.index',
                'label' => 'Deposit review',
                'pattern' => 'admin.deposits.*',
                'icon' => 'deposit',
                'count' => $pendingDeposits,
            ],
            [
                'route' => 'admin.users.index',
                'label' => 'Accounts',
                'pattern' => 'admin.users.*',
                'icon' => 'users',
                'count' => $pendingAccounts,
            ],
            [
                'route' => 'admin.payment-methods.index',
                'label' => 'Payment methods',
                'pattern' => 'admin.payment-methods.*',
                'icon' => 'card',
            ],
        ],
        'Later milestones' => [
            [
                'route' => 'admin.placeholder',
                'params' => ['section' => 'jobs'],
                'label' => 'Jobs',
                'pattern' => 'admin.placeholder.jobs',
                'icon' => 'briefcase',
                'soon' => true,
            ],
            [
                'route' => 'admin.placeholder',
                'params' => ['section' => 'escrow'],
                'label' => 'Escrow & releases',
                'pattern' => 'admin.placeholder.escrow',
                'icon' => 'lock',
                'soon' => true,
            ],
        ],
    ];

    $mobileTabs = [
        ['route' => 'admin.overview', 'label' => 'Overview', 'pattern' => 'admin.overview', 'icon' => 'grid'],
        ['route' => 'admin.deposits.index', 'label' => 'Deposits', 'pattern' => 'admin.deposits.*', 'icon' => 'deposit', 'count' => $pendingDeposits],
        ['route' => 'admin.users.index', 'label' => 'Accounts', 'pattern' => 'admin.users.*', 'icon' => 'users', 'count' => $pendingAccounts],
        ['route' => 'admin.payment-methods.index', 'label' => 'Methods', 'pattern' => 'admin.payment-methods.*', 'icon' => 'card'],
    ];

    $icon = function (string $name): string {
        return match ($name) {
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'deposit' => '<rect x="2.5" y="6" width="19" height="13" rx="2.5"/><path d="M2.5 10.5h19"/><path d="m7 15 2 2 4-4"/>',
            'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M2.8 19a6.4 6.4 0 0 1 12.4 0"/><path d="M17 8.5h4M19 6.5v4"/>',
            'card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19"/><path d="M6 15h4"/>',
            'briefcase' => '<rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/><path d="M3 12h18"/>',
            'lock' => '<rect x="4" y="10.5" width="16" height="10.5" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
            default => '',
        };
    };
@endphp

<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
            <span>
                {{ \App\Support\SiteSettings::brandName() }}
                <span class="sidebar-scope">Admin</span>
            </span>
        </div>

        <nav class="sidebar-nav" aria-label="Admin sections">
            @foreach ($navSections as $groupLabel => $items)
                <div class="sidebar-group">{{ $groupLabel }}</div>

                @foreach ($items as $item)
                    @php
                        $isCurrent = request()->routeIs($item['pattern']);
                        $href = route($item['route'], $item['params'] ?? []);
                    @endphp
                    <a href="{{ $href }}"
                       @if ($isCurrent) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon($item['icon']) !!}</svg>
                        {{ $item['label'] }}
                        @if (! empty($item['count']) && $item['count'] > 0)
                            <span class="nav-count">{{ $item['count'] }}</span>
                        @elseif (! empty($item['soon']))
                            <span class="nav-soon">M5</span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="sidebar-foot">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">
                <strong>Admin console</strong>
                <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
                separate from the customer app
            </div>
            <div class="topbar-actions">
                <span class="who">Signed in as <b>{{ auth()->user()->name }}</b></span>
                <a class="btn btn-secondary btn-sm" href="{{ route('home') }}">View site</a>
            </div>
        </div>

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
    </div>
</div>

{{-- Mobile tab bar. Same destinations as the sidebar's Operate group. --}}
<nav class="mobile-tabs" aria-label="Admin sections">
    @foreach ($mobileTabs as $tab)
        @php $isCurrent = request()->routeIs($tab['pattern']); @endphp
        <a href="{{ route($tab['route']) }}" @if ($isCurrent) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon($tab['icon']) !!}</svg>
            {{ $tab['label'] }}
            @if (! empty($tab['count']) && $tab['count'] > 0)
                <span class="mobile-tab-count">{{ $tab['count'] }}</span>
            @endif
        </a>
    @endforeach
</nav>

@stack('scripts')
</body>
</html>
