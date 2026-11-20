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
        .topbar-inner{max-width:1200px;margin:0 auto;padding:0 clamp(16px,4vw,32px);height:64px;display:flex;align-items:center;gap:1.5rem}
        .brand{display:flex;align-items:center;gap:.55rem;font-family:var(--serif);font-size:1.2rem;flex:none}
        .brand-mark{width:24px;height:24px;border-radius:5px;background:var(--forest);display:grid;place-items:center;color:#fff;font-family:var(--sans);font-size:.68rem;font-weight:600;flex:none}
        .brand-logo{height:24px;width:auto}
        .topnav{display:flex;gap:1.25rem;margin-left:auto;align-items:center;font-size:.875rem}
        .topnav a{color:var(--ink-2)}
        .topnav a:hover{color:var(--ink)}

        .wrap{max-width:1200px;margin:0 auto;padding:clamp(24px,4vw,44px) clamp(16px,4vw,32px) 4rem}
        h1{font-family:var(--serif);font-weight:400;font-size:clamp(1.6rem,3vw,2.1rem);line-height:1.1;letter-spacing:-.02em;margin:0 0 .35rem}
        .sub{color:var(--ink-3);font-size:.9rem;margin:0 0 1.75rem}

        .card{background:#fff;border:1px solid var(--rule);border-radius:14px;padding:1.4rem;box-shadow:0 1px 3px rgba(22,20,15,.04)}
        .card + .card{margin-top:1rem}

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
        .badge-deleted{background:var(--paper-3);color:var(--ink-3)}
        .badge-freelancer{background:var(--paper-2);color:var(--ink-2)}
        .badge-poster{background:var(--paper-2);color:var(--ink-2)}
        .badge-admin{background:var(--ink);color:var(--paper)}

        table{width:100%;border-collapse:collapse;font-size:.875rem}
        th{text-align:left;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);font-weight:600;padding:.6rem .5rem;border-bottom:1px solid var(--rule)}
        td{padding:.7rem .5rem;border-bottom:1px solid var(--rule);vertical-align:middle}
        tr:last-child td{border-bottom:0}
        .actions{display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end}

        .filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
        .filters select,.filters input{padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:var(--radius-sm);font:inherit;font-size:.85rem;background:#fff;color:var(--ink)}
    </style>
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
</body>
</html>
